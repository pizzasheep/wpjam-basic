<?php
function wpjam_basic_about_page(){
	?>
	<h1>关于WPJAM</h1>

	<div class="card">
		
		<h2>WPJAM Basic</h2>

		<p><strong><a href="http://blog.wpjam.com/project/wpjam-basic/">WPJAM Basic</a></strong> 是 <strong><a href="http://blog.wpjam.com/">我爱水煮鱼</a></strong> 的 Denis 开发的 WordPress 插件，WPJAM Basic 除了能够优化你的 WordPress ，也是 WordPress 果酱团队进行 WordPress 二次开发的基础。</p>

		<p>为了方便开发，WPJAM Basic 使用了最新的 PHP 7.2 语法，所以要使用该插件，需要你的服务器的 PHP 版本是 7.2 或者更高。</p>
		
		<p>更详细的 WordPress 优化请参考：<a href="https://blog.wpjam.com/article/wordpress-performance/">WordPress 性能优化：为什么我的博客比你的快</a>，我们也提供专业的 <a href="https://blog.wpjam.com/article/wordpress-optimization/">WordPress 性能优化服务</a>。</p>

	</div>

	<div class="card">

		<?php
		$wpjam_plugins = [
			'wpjam-collection'		=>	[
				'title'			=> '图片集',
				'description'	=> '1. 给媒体创建个分类「图片集 | collection」2. 图片分类限制为二级 3. 取消图片编辑入口 4. 附件页面直接图片链接。'
			],
			'wpjam-avatar'			=>	[
				'title'			=> '自定义头像',
				'description'	=> '管理员可以设置几个默认头像，用户可以在后台可以自定义头像，或者随机显示默认头像。'
			],
			'wpjam-series'			=>	[
				'title'			=> '文章专题',
				'description'	=> '在文章末尾显示专题文章列表的 WordPress 插件。'
			],
			'wpjam-taxonomy-levels'	=>	[
				'title'			=> '分类层级',
				'description'	=> '层式管理分类和限制分类层级的 WordPress 插件。'
			],
			'weixin-robot-advanced'	=>	[
				'title'			=> '微信机器人',
				'description'	=> '连接公众号和 WordPress 博客，匹配用户发送信息，匹配相关的文章，并自动回复用户。'
			],
			'weixin-group-qrcode'	=>	[
				'title'			=> '微信群二维码',
				'description'	=> '轮询显示微信群二维码，突破微信群100人限制。'
			],
			'weapp'					=>	[
				'title'			=> '微信小程序',
				'description'	=> '微信小程序 WordPress 基础插件，包含基础类库和管理。'
			],

			'wpjam-qiniutek'		=>	[
				'title'			=> '七牛云存储',
				'description'	=> '已经整合到 WPJAM Basic 插件之中，无须独立安装。'
			],
		];
		?>

		<h2>其他插件</h2>

		<table class="widefat fixed striped">
			<tbody>
			<?php foreach($wpjam_plugins as $plugin_key => $wpjam_plugin){ ?>
				<tr>
					<th style="width: 90px;"><strong><a href="https://blog.wpjam.com/project/<?php echo $plugin_key; ?>/"><?php echo $wpjam_plugin['title']; ?></a></strong></th>
					<td><?php echo $wpjam_plugin['description']; ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>

		<p>我们开发的这些插件都需要<strong>首先安装</strong> WPJAM Basic，其他功能插件将以扩展的模式整合到 WPJAM Basic 插件一并发布。</p>
		
	</div>

	<?php 
}