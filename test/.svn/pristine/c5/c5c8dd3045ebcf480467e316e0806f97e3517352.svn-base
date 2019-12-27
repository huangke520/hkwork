<?php

namespace app\index\controller;

class Index
{
    public function index()
    {
        //$redis = get_redis();
        //$redis = get_redis_pro();
        // $redis->set('zhang','hello, this is api server!',60*10);
        //echo $redis->get('zhang');
        /*
        $a = 'a:18:{s:2:"id";s:3:"429";s:7:"uniacid";s:1:"4";s:6:"openid";s:35:"sns_wa_ogrIh0dGSJlE59Qg-fR4IndJXHHY";s:8:"realname";s:9:"陈女士";s:6:"mobile";s:11:"13341016098";s:8:"province";s:0:"";s:4:"city";s:0:"";s:4:"area";s:0:"";s:7:"address";s:66:"北京市丰台区右外东庄21号楼 便民菜市场  幸福4巷";s:9:"isdefault";s:1:"1";s:7:"zipcode";s:0:"";s:7:"deleted";s:1:"0";s:6:"street";s:0:"";s:9:"datavalue";s:0:"";s:15:"streetdatavalue";s:0:"";s:3:"lng";s:8:"116.3766";s:3:"lat";s:8:"39.86935";s:8:"is_range";s:2:"-1";}';
        $b=unserialize($a);
        print_r($b);
        foreach($b as $k => $v){
            if($k == 'address'){
                $b[$k] = str_replace(' ','',$v);
            }
        }
        $c = $b;
        print_r($c);
        $d = serialize($c);
        echo $d;
        */
        $img = 'http://wx.qlogo.cn/mmhead/B2EfAOZfS1jSlGWs5r4TGLrByPhGqNib3OQ36npLt0IF4KUC0usEIicw/132';
        var_dump($this->check_remote_file_exists($img));
        echo "<br>";
        var_dump($this->img_exists($img));
        echo "<br>";

        return;//'<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    // 判断文件是否存在
    public function img_exists($url)
    {
        echo "b ".time()."<br>";
        if (@file_get_contents($url, 0, null, 0, 1)) {
            echo time()."<br>";
            return true;
        } else {
            echo time()."<br>";
            return false;
        }
    }

    //判断远程文件
    public function check_remote_file_exists($url)
    {
        echo "a ".time()."<br>";
        $curl = curl_init($url);
        // 不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        // 发送请求
        $result = curl_exec($curl);
        $found = false;
        echo time()."<br>";
        // 如果请求没有发送失败
        if ($result !== false) {
            // 再检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                echo time()."<br>";
                $found = true;
            }
            curl_close($curl);
            return $found;
        }
    }


}
