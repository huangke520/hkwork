<?php
namespace app\common\validate;

use think\Validate;

class Project extends Validate
{
	protected $rule = [
	        'title'  =>  'require|max:64',
	        'img' =>  'require',
	        
	    ];

	protected $message  =   [
	        'title.require' => '名称不能为空',
	        'title.max'     => '名称最多不能超过64个字符',
	        'img.require' => '封面不能为空',
	    ];
}
