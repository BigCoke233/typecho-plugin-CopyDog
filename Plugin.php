<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * CopyDog：版权狗插件
 * 
 * @package CopyDog
 * @author Eltrac
 * @version 1.0.0
 * @link https://github.com/BigCoke233
 */
 
class CopyDog_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('Widget_Archive')->___copyDog = array('CopyDog_Plugin', 'copydog');
		Typecho_Plugin::factory('Widget_Archive')->header = array('CopyDog_Plugin','header');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
	
	/**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
		echo '<style>.typecho-option li span{display:block}</style>
		<p>关于设置以及许可协议的说明请查看<a href="https://github.com/BigCoke233/typecho-plugin-CopyDog/tree/main/README.md" target="_blank">说明文档</a></p>';
        //个性化
		$license = new Typecho_Widget_Helper_Form_Element_Radio('license', array(
			'by' => _t('CC BY（要求署名）'),
			'by-sa' => _t('CC BY-SA（要求署名，相同协议原则）'),
			'by-nd' => _t('CC BY-ND（要求署名，禁止二次创作后分发）'),
			'by-nc' => _t('CC BY-NC（要求署名，禁止商用）'),
			'by-nc-sa' => _t('CC BY-NC-SA（要求署名，禁止商用，相同协议原则）'),
			'by-nc-nd' => _t('CC BY-NC-ND（要求署名，禁止商用，禁止二次创作后分发）'),
			'ARR' => _t('All Rights Reserved.（保留所有权利）'),
			'PD' => _t('Public Domain（属于公共领域）'),
			'CC0' => _t('CC0 No Rights Reserved（不保留任何权利）')
			
		),
		'by',
		_t('选择默认的许可协议'),
		_t('当一篇文章没有指定一个许可协议的时候，就会使用这里的默认协议。')
		);
		$form->addInput($license->addRule('required', _t('此处必须设置')));
		
		$button = new Typecho_Widget_Helper_Form_Element_Radio('button', array(
			'y' => _t('启用'),
			'n' => _t('不启用'),
		),
		'y',
		_t('是否启用「复制版权信息按钮」'),
		_t('若启用，版权信息卡片右下角会显示「复制版权信息按钮」，点击后会将当前文章的版权信息粘贴到用户的剪切板。')
		);
		$form->addInput($button->addRule('required', _t('此处必须设置')));
    }
	
	/**
	 * 在头部输出内容
	 * 主要用于引入 css 文件
	 */
	public static function header()
    {
		$dir = Helper::options()->pluginUrl.'/CopyDog';
		echo "<link rel=\"stylesheet\" href=\"{$dir}/style.css\">\n
		<script>copydog_copied=function(){};</script>";
		
    }

	/**
	 * 生成许可协议链接
	 */
	static public function license($license_id)
	{
		if($license_id=='ARR'){
			$license_link='无，保留所有权利';
		}
		elseif($license_id=='PD'){
			$license_link='<span class="iconfont">&#xf184;</span> 作品属于公共领域';
		}
		elseif($license_id=='CC0'){
			$license_link='<span class="iconfont">&#xe605;</span> 放弃所有权利';
		}
		else{
			$license_url='https://creativecommons.org/licenses/'.$license_id.'/4.0/';
			$license=strtoupper($license_id);
			
			$license_icon='<span class="iconfont">&#xe601;</span><span class="iconfont">&#xe600;</span>';
			if(strpos($license_id, 'nc')){
				$license_icon=$license_icon.'<span class="iconfont">&#xe602;</span>';
			}
			if(strpos($license_id, 'nd')){
				$license_icon=$license_icon.'<span class="iconfont">&#xe604;</span>';
			}
			if(strpos($license_id, 'sa')){
				$license_icon=$license_icon.'<span class="iconfont">&#xe603;</span>';
			}
			$license_link='<a href="'.$license_url.'" target="_blank">'.$license_icon.' CC '.$license.' 4.0</a>';
		}
		
		return $license_link;
	}
	
	/**
	 * 输出版权信息
	 */
	static public function copydog($post)
    {
		//生成许可协议链接
		$license_id='';
		if($post->fields->copydog=='off'){
			return false;
		}elseif($post->fields->copydog!=''){
			$license_id=$post->fields->copydog;
			$license_link=CopyDog_Plugin::license($license_id);
			$license_name='CC '.strtoupper($post->fields->copydog).' 4.0';
		}else{
			$license_id=Typecho_Widget::widget('Widget_Options')->plugin('CopyDog')->license;
			$license_link=CopyDog_Plugin::license($license_id);
			$license_name='CC '.strtoupper($license_id).' 4.0';
		}
		//构建复制版权信息按钮
		$button='';
		if(Typecho_Widget::widget('Widget_Options')->plugin('CopyDog')->button && (strstr($license_id, 'by'))){
			$script='<script>
			copydog_btn = document.querySelector("#copydog-copy");
			copydog_btn.addEventListener("click",function(){
				navigator.clipboard.writeText("本文「'.$post->title.'」原文地址为 '.$post->permalink.'，转载/修改已获得 '.$license_name.' 协议授权。");
				copydog_btn.innerText="复制成功";
				copydog_copied();
				setTimeout(function(){copydog_btn.innerText="复制版权信息";}, 3000)
			});
			</script>';
			$button='<button id="copydog-copy">复制版权信息</button>'.$script;
		}
		
		//输出卡片内容
		echo '<section id="copydog-declaration">
			<div class="copydog-post">
				<h5 class="copydog-post-title">'.$post->title.'</h5>
				<p class="copydog-post-link"><a href="'.$post->permalink.'">'.$post->permalink.'</a></p>
			</div>
			<div class="copydog-info">
				<div class="copydog-info-item" id="copydog-author">
					<h5 class="copydog-info-title">作者</h5>
					<p class="copydog-info-content">';
		$post->author();
		echo'</p>
				</div>
				<div class="copydog-info-item" id="copydog-date">
					<h5 class="copydog-info-title">发布时间</h5>
					<p class="copydog-info-content">';
		echo $post->date();
		echo '</p>
				</div>
				<div class="copydog-info-item" id="copydog-license">
					<h5 class="copydog-info-title">许可协议</h5>
					<p class="copydog-info-content">'.$license_link.'</p>
				</div>
			</div>'.$button.'
		</section>';
    }
}
