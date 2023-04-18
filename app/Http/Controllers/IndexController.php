<?php

namespace App\Http\Controllers;

use Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IndexController extends Controller
{
    //链接格式
    //$url = 'https://www.douyin.com/discover?modal_id=7090025725020933390';
    //$url = 'https://www.douyin.com/video/6687535180929830147';
    //$url = 'http://v.douyin.com/uycNy6';
    public function douyin(Request $request)
    {
        if ($request->isMethod('post')) {

            $validator = Validator::make($request->all(), [
                'url' => 'required|url|regex:/douyin/m',
            ]);

            if ($validator->fails()) {
                Alert::error('Error', 'Please use valid url !');
                return array('code' => 201, 'msg' => '解析失败');
            }
            $url = $request->get('url');
            $loc = get_headers($url, true)['Location'] ?? '';
            preg_match('/[0-9]+/', $loc, $id);
            if (empty($id)) {
                preg_match('/[0-9]+/', $url, $id);
            }

            if (empty($id)) {
                Alert::error('Error', 'Please use valid url !');
                return array('code' => 201, 'msg' => '解析失败');
            }

            // 关于这里的第三方接口问题 请查看 https://github.com/5ime/video_spider#faq
            $url = 'https://tiktok.iculture.cc/X-Bogus';
            $data = json_encode(array('url' => 'https://www.douyin.com/aweme/v1/web/aweme/detail/?aweme_id=' . $id[0] . '&aid=1128&version_name=23.5.0&device_platform=android&os_version=2333','user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36'));
            $header = array('Content-Type: application/json');
            $url = json_decode($this->curl($url, $header, $data), true)['param'];

            $msToken = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 107);
            $header = array('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', 'Referer: https://www.douyin.com/', 'Cookie: msToken='.$msToken.';odin_tt=324fb4ea4a89c0c05827e18a1ed9cf9bf8a17f7705fcc793fec935b637867e2a5a9b8168c885554d029919117a18ba69; ttwid=1%7CWBuxH_bhbuTENNtACXoesI5QHV2Dt9-vkMGVHSRRbgY%7C1677118712%7C1d87ba1ea2cdf05d80204aea2e1036451dae638e7765b8a4d59d87fa05dd39ff; bd_ticket_guard_client_data=eyJiZC10aWNrZXQtZ3VhcmQtdmVyc2lvbiI6MiwiYmQtdGlja2V0LWd1YXJkLWNsaWVudC1jc3IiOiItLS0tLUJFR0lOIENFUlRJRklDQVRFIFJFUVVFU1QtLS0tLVxyXG5NSUlCRFRDQnRRSUJBREFuTVFzd0NRWURWUVFHRXdKRFRqRVlNQllHQTFVRUF3d1BZbVJmZEdsamEyVjBYMmQxXHJcbllYSmtNRmt3RXdZSEtvWkl6ajBDQVFZSUtvWkl6ajBEQVFjRFFnQUVKUDZzbjNLRlFBNUROSEcyK2F4bXAwNG5cclxud1hBSTZDU1IyZW1sVUE5QTZ4aGQzbVlPUlI4NVRLZ2tXd1FJSmp3Nyszdnc0Z2NNRG5iOTRoS3MvSjFJc3FBc1xyXG5NQ29HQ1NxR1NJYjNEUUVKRGpFZE1Cc3dHUVlEVlIwUkJCSXdFSUlPZDNkM0xtUnZkWGxwYmk1amIyMHdDZ1lJXHJcbktvWkl6ajBFQXdJRFJ3QXdSQUlnVmJkWTI0c0RYS0c0S2h3WlBmOHpxVDRBU0ROamNUb2FFRi9MQnd2QS8xSUNcclxuSURiVmZCUk1PQVB5cWJkcytld1QwSDZqdDg1czZZTVNVZEo5Z2dmOWlmeTBcclxuLS0tLS1FTkQgQ0VSVElGSUNBVEUgUkVRVUVTVC0tLS0tXHJcbiJ9');
            $arr = json_decode($this->curl($url, $header), true);
            $video_url = $arr['aweme_detail']['video']['play_addr']['url_list'][0];

            if (empty($video_url)) {
                return array('code' => 201, 'msg' => '解析失败');
            }

            $arr = array(
                'code' => 200,
                'msg' => '解析成功',
                'data' => array(
                    'author' => $arr['aweme_detail']['author']['nickname'],
                    'uid' => $arr['aweme_detail']['author']['unique_id'],
                    'avatar' => $arr['aweme_detail']['music']['avatar_large']['url_list'][0],
                    'like' => $arr['aweme_detail']['statistics']['digg_count'],
                    'time' => $arr['aweme_detail']["create_time"],
                    'title' => $arr['aweme_detail']['desc'],
                    'cover' => $arr['aweme_detail']['video']['origin_cover']['url_list'][0],
                    'url' => $arr['aweme_detail']['video']['play_addr']['url_list'][0],
                    'music' => array(
                        'author' => $arr['aweme_detail']['music']['author'],
                        'avatar' => $arr['aweme_detail']['music']['cover_large']['url_list'][0],
                        'url' => $arr['aweme_detail']['music']['play_url']['url_list'][0],
                    )
                )
            );
            return $arr;

        } else {
            return view('index');
        }
    }

    public function downloadVideo(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'option' => 'required',
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Please check your requests');
            return redirect('/');
        }

        if ($request->option == 'without_watermark') {
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                'GET',
                'https://api2.musical.ly/aweme/v1/aweme/detail/?aweme_id=' . $request->video_id,
                ['http_errors' => false]
            );
            $uri = json_decode($response->getBody()->getContents(), true)['aweme_detail']['video']['play_addr']['uri'];
            return redirect()->away('https://api2-16-h2.musical.ly/aweme/v1/play/?video_id=' . $uri . '&vr_type=0&is_play_url=1&source=PackSourceEnum_PUBLISH&media_type=4');
        } else {
            return redirect()->away($request->video_id);
        }

    }
}
