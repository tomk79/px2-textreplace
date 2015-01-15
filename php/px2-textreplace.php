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
					$this->error( '未定義のコマンドを受け付けました。' );
				}
				break;
		}

		$this->error();
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
		$this->user_message('このコマンドは、Pickles2 内部のコードを検索、置換するインターフェイスを提供します。');
		exit;
	}

	/**
	 * 検索する
	 * 
	 * @return void
	 */
	private function fnc_search(){
		header('Content-type: text/plain;');
		print 'search'."\n";
		exit;
	}

	/**
	 * 置換する
	 * 
	 * @return void
	 */
	private function fnc_replace(){
		header('Content-type: text/plain;');
		print 'replace'."\n";
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
	private function error( $msg ){
		$this->user_message( $msg );
		exit;
	}

	/**
	 * ユーザーへのメッセージを表示して終了する
	 * @param string $msg メッセージテキスト
	 */
	private function user_message($msg){
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