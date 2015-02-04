<?php
/**
 * px2-textreplace.php
 */
namespace tomk79\pickles2\textreplace;

/**
 * px2-textreplace.php
 */
class pickles_textreplace{

	/**
	 * Picklesオブジェクト
	 */
	private $px;

	/**
	 * PXコマンド名
	 */
	private $command = array();

	/**
	 * 一時書き込み用ディレクトリのパス
	 */
	private $realpath_tmp_dir;

	/**
	 * entry
	 */
	static public function register($px){
		$px->pxcmd()->register('textreplace', function($px){
			(new self( $px ))->kick();
			exit;
		}, true);
	}

	/**
	 * px2-textreplace のバージョン情報を取得する。
	 * 
	 * <pre> [バージョン番号のルール]
	 *    基本
	 *      メジャーバージョン番号.マイナーバージョン番号.リリース番号
	 *        例：1.0.0
	 *        例：1.8.9
	 *        例：12.19.129
	 *      - 大規模な仕様の変更や追加を伴う場合にはメジャーバージョンを上げる。
	 *      - 小規模な仕様の変更や追加の場合は、マイナーバージョンを上げる。
	 *      - バグ修正、ドキュメント、コメント修正等の小さな変更は、リリース番号を上げる。
	 *    開発中プレビュー版
	 *      基本バージョンの後ろに、a(=α版)またはb(=β版)を付加し、その連番を記載する。
	 *        例：1.0.0a1 ←最初のα版
	 *        例：1.0.0b12 ←12回目のβ版
	 *      開発中およびリリースバージョンの順序は次の通り
	 *        1.0.0a1 -> 1.0.0a2 -> 1.0.0b1 ->1.0.0b2 -> 1.0.0 ->1.0.1a1 ...
	 *    ナイトリービルド
	 *      ビルドの手順はないので正確には "ビルド" ではないが、
	 *      バージョン番号が振られていない、開発途中のリビジョンを
	 *      ナイトリービルドと呼ぶ。
	 *      ナイトリービルドの場合、バージョン情報は、
	 *      ひとつ前のバージョン文字列の末尾に、'-nb' を付加する。
	 *        例：1.0.0b12-nb (=1.0.0b12リリース後のナイトリービルド)
	 *      普段の開発においてコミットする場合、
	 *      必ずこの get_version() がこの仕様になっていることを確認すること。
	 * </pre>
	 * 
	 * @return string バージョン番号を示す文字列
	 */
	public function get_version(){
		return '2.0.0a1-nb';
	}

	/**
	 * constructor
	 */
	public function __construct( $px ){
		$this->px = $px;
		$this->realpath_tmp_dir = $this->px->realpath_plugin_private_cache('tmp/');
		$this->px->fs()->mkdir( $this->realpath_tmp_dir );
	}

	/**
	 * 一時保存ディレクトリの絶対パスを取得する
	 */
	public function get_realpath_tmp_dir(){
		return $this->realpath_tmp_dir;
	}

	/**
	 * kick
	 */
	private function kick(){
		$this->command = $this->px->get_px_command();

		switch( @$this->command[1] ){
			case 'search':
				// 検索する
				$this->fnc_search();
				break;
			case 'replace':
				// 置換する
				$this->fnc_replace();
				break;
			case 'ping':
				// 疎通確認応答
				$this->fnc_ping();
				break;
			default:
				//各種情報の取得
				if( !strlen( @$this->command[1] ) ){
					$this->homepage();
				}else{
					$this->error_message_end( '未定義のコマンドを受け付けました。' );
				}
				break;
		}

		$this->error_message_end();
		exit;
	}

	/**
	 * ホームページを表示する。
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @return void
	 */
	private function homepage(){
		if( $this->px->req()->is_cmd() ){
			$this->user_message_end('このコマンドは、Pickles2 内部のコードを検索、置換するインターフェイスを提供します。');
		}else{
			$html = '';
			ob_start(); ?>
<p>Pickles2 内部のコードを検索、置換します。</p>
<script>
	$(window).load(function(){
		var $replaceForm = $('.cont_replace_form');
		var $result = $('.cont_result');
		$( 'input[name=q]' ).change(function(){
			$('.cont_mirror_of_q').text( $(this).val() );
		});
		$( 'input[name=selector]' ).change(function(){
			$('.cont_mirror_of_selector').text( $(this).val() );
		});
		// $replaceForm.hide();
		$('input[name=replace]')
			.change(function(){
				var $this = $(this);
				if( $this.get(0).checked ){
					$replaceForm.stop().hide().fadeIn('fast');
				}else{
					$replaceForm.stop().show().fadeOut('fast');
				}
			})
		;
		$('.cont_form_search_and_replace').submit(function(){
			var replaceFlg = $('input[name=replace]').get(0).checked;
			var $form = $('.cont_form_search_and_replace');
			$result.html('');
			$('.cont_result').fadeIn('slow');
			$.ajax({
				url: '?PX=textreplace.'+(replaceFlg?'replace':'search'),
				data:{
					q: $form.find('[name=q]').val() ,
					q_regexp: ($form.find('[name=q_regexp]').get(0).checked?1:0) ,
					q_case_strict: ($form.find('[name=q_case_strict]').get(0).checked?1:0) ,
					target_contents: ($form.find('[name=target_contents]').get(0).checked?1:0) ,
					target_sitemaps: ($form.find('[name=target_sitemaps]').get(0).checked?1:0) ,
					target_homedir: ($form.find('[name=target_homedir]').get(0).checked?1:0) ,
					contents_region: $form.find('[name=contents_region]').val(),
					selector: $form.find('[name=selector]').val(),
					replace_str: $form.find('[name=replace_str]').val(),
					replace_dom: $form.find('[name=replace_dom]').val()
				} ,
				success: function(data){
					// $result.text( $result.text()+data );
					var $tbody = $('<tbody>');
					$result
						.append( $('<table class="def" style="width:100%;">')
							.append( $('<thead>')
								.append( $('<tr>')
									.append( $('<th>').text('path') )
									.append( $('<th>').text('type') )
									.append( $('<th>').text('size') )
									.append( $('<th>').text('charset') )
									.append( $('<th>').text('crlf') )
									.append( $('<th>').text('path_division') )
								)
							)
							.append( $tbody )
						)
					;
					for( var idx = 0; idx < data.results.length; idx ++ ){
						$tbody
							.append( $('<tr>')
								.append( $('<td>').text( data.results[idx].path ) )
								.append( $('<td>').text( data.results[idx].type ) )
								.append( $('<td>').text( data.results[idx].size ) )
								.append( $('<td>').text( data.results[idx].charset ) )
								.append( $('<td>').text( data.results[idx].crlf ) )
								.append( $('<td>').text( data.results[idx].path_division ) )
							)
						;
						// console.log( data.results[idx] );
					}
				} ,
				error: function(err){
					console.log(err);
					alert('ERROR!');
				} ,
				complete: function(){
				}
			})
			return false;
		});
	});
</script>
<form action="javascript:;" method="get" class="cont_form_search_and_replace">
<div class="unit">
	<table class="form_elements">
		<thead>
			<tr>
				<th>入力項目名</th>
				<th>入力フィールド</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>検索文字列</th>
				<td>
					<input type="text" name="q" value="" placeholder="検索文字列" style="width:100%;" />
					<ul class="form_elements-list">
						<li><label><input type="checkbox" name="q_regexp" value="1" /> 正規表現を有効にする</label></li>
						<li><label><input type="checkbox" name="q_case_strict" value="1" /> 大文字と小文字を区別する</label></li>
					</ul>
				</td>
			</tr>
			<tr>
				<th>対象範囲</th>
				<td>
					<ul class="form_elements-notes">
						<li>検索・置換処理の対象とする要素を選択してください。</li>
					</ul>
					<ul class="form_elements-list">
						<li><label><input type="checkbox" name="target_contents" value="1" checked="checked" /> コンテンツ</label></li>
						<li><label><input type="checkbox" name="target_sitemaps" value="1" checked="checked" /> サイトマップ</label></li>
						<li><label><input type="checkbox" name="target_homedir"  value="1" checked="checked" /> ホームディレクトリ全体</label></li>
					</ul>
					<ul class="form_elements-notes">
						<li>コンテンツのパスを指定してください。</li>
						<li>省略時、すべてのコンテンツファイルが対象になります。</li>
					</ul>
					<input type="text" name="contents_region" value="" placeholder="/" style="width:100%;" />
					<ul class="form_elements-notes">
						<li>CSSセレクタの形式で、検索対象のDOM構造的な範囲を指定してください。</li>
						<li>空白のまま実行すると、全領域が対象になります。</li>
					</ul>
					<input type="text" name="selector" value="" placeholder="CSSセレクタを指定" style="width:100%;" />
				</td>
			</tr>
		</tbody>
	</table>
</div><!-- /.unit -->

<p><label><input type="checkbox" name="replace" value="1" /> 置換する</label></p>
<div class="cont_replace_form" style="display:none;">
	<div class="unit">
		<table class="form_elements">
			<thead>
				<tr>
					<th>入力項目名</th>
					<th>入力フィールド</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>置換文字列</th>
					<td>
						<ul class="form_elements-notes">
							<li><q class="cont_mirror_of_q"></q> を置き換える文字列を入力してください。</li>
						</ul>
						<input type="text" name="replace_str" value="" style="width:100%;" />
					</td>
				</tr>
				<tr>
					<th>置換DOM構造</th>
					<td>
						<ul class="form_elements-notes">
							<li><q class="cont_mirror_of_selector"></q> を置き換えるCSSセレクタを入力してください。</li>
						</ul>
						<input type="text" name="replace_dom" value="" style="width:100%;" />
					</td>
				</tr>
			</tbody>
		</table>
	</div><!-- /.unit -->
</div><!-- /.cont_replace_form -->
<div class="unit form_buttons">
	<ul>
		<li class="form_buttons-submit"><input type="submit" name="" value="検索する" /></li>
	</ul>
</div><!-- /.form_buttons -->
</form>

<div class="cont_result" style="display:none;">
	---
</div>


<?php
			$html .= ob_get_clean();
			print $this->px->pxcmd()->wrap_gui_frame($html);
		}
		exit;
	}

	/**
	 * 検索する
	 * 
	 * @return void
	 */
	private function fnc_search(){
		header('Content-type: application/json;');

		$searcher = $this->create_searcher();
		$results = $searcher->get_results();
		print json_encode( $results );

		exit;
	}
	/**
	 * 検索オブジェクトを生成する
	 * 
	 * @return object searcher
	 */
	private function create_searcher(){
		$query = array();
		$query['q'] = $this->px->req()->get_param('q');
		$query['q_regexp'] = !empty($this->px->req()->get_param('q_regexp'));
		$query['q_case_strict'] = !empty($this->px->req()->get_param('q_case_strict'));
		$query['target_contents'] = !empty($this->px->req()->get_param('target_contents'));
		$query['target_sitemaps'] = !empty($this->px->req()->get_param('target_sitemaps'));
		$query['target_homedir']  = !empty($this->px->req()->get_param('target_homedir'));
		$query['contents_region'] = $this->px->req()->get_param('contents_region');
		$query['selector'] = $this->px->req()->get_param('selector');

		require_once( __DIR__.'/searcher/searcher.php' );
		$searcher = (new searcher( $this->px, $this ))->search( $query );
		return $searcher;
	}// create_searcher();


	/**
	 * 置換する
	 * 
	 * @return void
	 */
	private function fnc_replace(){
		header('Content-type: application/json;');

		$searcher = $this->create_searcher();
		$results = $searcher->get_results();
		// print json_encode( $results );

		require_once( __DIR__.'/replacer/replacer.php' );
		$results['query']['replace_str'] = $this->px->req()->get_param('replace_str');
		$results['query']['replace_dom'] = $this->px->req()->get_param('replace_dom');

		$replacer = (new replacer( $this->px, $this ))->replace( $results['query'] );
		$results = $replacer->get_results();
		print json_encode( $results );

		exit;
	}

	/**
	 * 疎通確認応答
	 * 
	 * @return void
	 */
	private function fnc_ping(){
		header('Content-type: text/plain;');
		print 'ok'."\n";
		exit;
	}

	/**
	 * エラーメッセージを表示する。
	 * 
	 * HTMLを標準出力した後、`exit()` を発行してスクリプトを終了します。
	 * 
	 * @param string $msg エラーメッセージ
	 * @return void
	 */
	private function error_message_end( $msg ){
		$this->user_message_end( $msg );
		exit;
	}

	/**
	 * ユーザーへのメッセージを表示して、スクリプトを終了する
	 * @param string $msg メッセージテキスト
	 */
	private function user_message_end($msg){
		if( $this->px->req()->is_cmd() ){
			header('Content-type: text/plain;');
			print $this->px->pxcmd()->get_cli_header();
			print $msg."\n";
			print $this->px->pxcmd()->get_cli_footer();
		}else{
			$html = '';
			ob_start(); ?>
	<p><?= htmlspecialchars($msg) ?></p>
<?php
			$html .= ob_get_clean();
			print $this->px->pxcmd()->wrap_gui_frame($html);
		}
		exit;
	}

}