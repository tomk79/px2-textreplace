<?php
/**
 * searcher.php
 */
namespace tomk79\pickles2\textreplace;

/**
 * searcher.php
 */
class searcher{

	/**
	 * Picklesオブジェクト
	 */
	private $px;

	/**
	 * アプリケーションメインオブジェクト
	 */
	private $main;

	/**
	 * 検索クエリ
	 */
	private $query;

	/**
	 * パスなど
	 */
	private $realpath_base, $realpath_cond, $realpath_filelist;

	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param object $main textreplaceのメインオブジェクト
	 */
	public function __construct( $px, $main ){
		$this->px = $px;
		$this->main = $main;

		$this->realpath_base = $this->px->fs()->get_realpath( $this->px->get_path_docroot().$this->px->get_path_controot() );
		$this->realpath_cond = $this->main->get_realpath_tmp_dir().'cond.json';
		$this->realpath_filelist = $this->main->get_realpath_tmp_dir().'target_file_list.csv';
	}

	/**
	 * 検索を実行する
	 * @param array $query 検索クエリ
	 * @return object searcherオブジェクト
	 */
	public function search( $query ){
		$this->query = $query;

		// var_dump( $this->px->get_path_homedir() );

		// 初期化
		$this->px->fs()->save_file( $this->realpath_filelist, $this->px->fs()->mk_csv(array( array('path','type','size','charset','crlf','path_division') )) );
		$this->px->fs()->save_file( $this->realpath_cond, json_encode( $this->query ) );

		// ファイルリストを作成する
		$this->scan_dir();


		// // PHP Simple HTML DOM Parser
		// // https://packagist.org/packages/sunra/php-simple-html-dom-parser
		// // 鉄板だけあって、検索も変更も対応している。
		// // ...んだけど、再構成したHTMLが元とだいぶ変わってしまう。
		// // 改行が全部なくなる。これだと使えない...。
		// // $html = '<!DOCTYPE html><html><head><title>test</title></head><body></body></html>';
		// $html = $this->px->fs()->read_file( './index.html' );
		// $dom = \Sunra\PhpSimple\HtmlDomParser::str_get_html( $html );
		// $elems = $dom->find( 'li' );
		// // var_dump($elems);
		// var_dump(array_keys(get_object_vars($dom)));
		// var_dump($dom->outertext);
		// var_dump($dom->outerhtml);

		// // Symfony の DomCrawler を直に使うテスト
		// // スタティックなHTMLコードを受け取ってはくれるが、
		// // 加工後のコードを再構成するような機能はない。
		// $html = '<html><body><ul><li class="odd">spam</li><li class="even">egg</li><li class="odd">ham</li></ul></body></html>';
		// $crawler = new \Symfony\Component\DomCrawler\Crawler($html, 'http://localhost/');
		// $element = $crawler
		// 	->filter('li')
		// 	->reduce( function ($node, $i) {
		// 		if ($node->attr('class') !== 'even') {
		// 			return false;
		// 		}
		// 	} )
		// 	->first()
		// ;
		// echo( $element->text() );

		// // Goutte にスタティックなHTMLコードを食わせてみるテスト
		// // HTMLコードを直接受け取ってはくれないみたい。
		// // 普通に 501 を拾った。
		// $html = '<html><head><title></title></head><body></body></html>';
		// // require_once './profiler.php';
		// $client = new \Goutte\Client;
		// $crawler = $client->request($html, 'http://dropbox.localhost/');
		// $crawler->filter('title')->each(function($node) {
		// 	echo trim($node->text()) . "\n";
		// });

		return $this;
	}

	/**
	 * ディレクトリをスキャンして一覧を作る
	 */
	private function scan_dir( $path = '' ){
		$ls = $this->px->fs()->ls( $this->realpath_base.$path );
		// var_dump($ls);
		foreach( $ls as $basename ){
			$tmp_path = $this->px->fs()->get_realpath( $this->realpath_base.$path.'/'.$basename );
			if( $this->px->fs()->is_file( $tmp_path ) ){
				// ファイル
				$result = $this->scan_file( $this->px->fs()->get_realpath('/'.$path.'/'.$basename) );
				if( $result === false ){
					// 検索クエリにマッチしない
					continue;
				}

				error_log( $this->px->fs()->mk_csv( $result ), 3, $this->realpath_filelist );
				// echo( json_encode($csv_row) );
				flush();
			}elseif( $this->px->fs()->is_dir( $tmp_path ) ){
				// ディレクトリ
				$this->scan_dir( $path.'/'.$basename );
			}
		}
		return $this;
	}

	/**
	 * ファイル内容を検索し、マッチするか否かを返す
	 */
	private function scan_file( $path ){
		$rtn = array(
			'path'=>null,
			'proc_type'=>null,
			'size'=>null,
			'charset'=>null,
			'crlf'=>null,
			'path_division'=>null,
		);
		$rtn['path'] = $path;
		$rtn['proc_type'] = $this->px->get_path_proc_type( $rtn['path'] );
		if( $rtn['proc_type'] == 'ignore' ){
			// 除外パスの場合
			// return false;
		}

		$rtn['size'] = filesize( $this->realpath_base.$path );

		$realpath_file = $this->px->fs()->get_realpath($this->realpath_base.$path);
		$realpath_home_dir = $this->px->fs()->get_realpath($this->px->get_path_homedir());
		$realpath_sitemap_dir = $this->px->fs()->get_realpath($this->px->get_path_homedir().'sitemaps/');
		$rtn['path_division'] = 'contents';
		if( preg_match( '/^'.preg_quote( $realpath_home_dir, '/' ).'/s', $realpath_file ) ){
			$rtn['path_division'] = 'homedir';
		}
		if( preg_match( '/^'.preg_quote( $realpath_sitemap_dir, '/' ).'/s', $realpath_file ) ){
			$rtn['path_division'] = 'sitemaps';
		}
		switch( $rtn['path_division'] ){
			case 'contents':
				if( !$this->query['target_contents'] ){ return false; }
				if( !preg_match( '/^'.preg_quote( $this->query['contents_region'], '/' ).'.*$/s', $rtn['path'] ) ){ return false; }
				break;
			case 'homedir':
				if( !$this->query['target_homedir'] ){ return false; }
				break;
			case 'sitemaps':
				if( !$this->query['target_sitemaps'] ){ return false; }
				break;
		}

		$bin = $this->px->fs()->read_file( $realpath_file );
		if( !strlen( $this->query['q'] ) ){
			return false;
		}
		$regexp = '/'.preg_quote( $this->query['q'], '/' ).'/s';
		if( $this->query['q_regexp'] ){
			$regexp = '/'.$this->query['q'].'/s';
		}
		if( !$this->query['q_case_strict'] ){
			$regexp .= 'i';
		}
		try{
			if( !preg_match( $regexp, $bin ) ){
				return false;
			}
		}catch( Exception $e ){
			return false;
		}

		$rtn['charset'] = mb_detect_encoding( $bin, "UTF-8,SJIS-mac,SJIS-win,SJIS,eucJP-win,EUC-JP,JIS" );
		$crlf = array();
		if( preg_match( '/\r\n/si', $bin ) ){
			array_push($crlf, 'CRLF');
		}
		$bin = preg_replace( '/\r\n/si', '', $bin );
		if( preg_match( '/\r/si', $bin ) ){
			array_push($crlf, 'CR');
		}
		if( preg_match( '/\n/si', $bin ) ){
			array_push($crlf, 'LF');
		}
		$rtn['crlf'] = implode( '/', $crlf );


		return array($rtn);
	}

	/**
	 * 検索結果を取得する
	 */
	public function get_results(){
		$cond = json_decode( $this->px->fs()->read_file( $this->realpath_cond ) );
		$csv = $this->px->fs()->read_csv( $this->realpath_filelist );
		$csv = (new \tomk79\csv2json( $this->realpath_filelist ))->fetch_assoc();

		return array( 'cond'=>$cond, 'results'=>$csv );
	}

}