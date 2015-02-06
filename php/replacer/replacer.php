<?php
/**
 * replacer.php
 */
namespace tomk79\pickles2\textreplace;

/**
 * replacer.php
 */
class replacer{

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
	private $realpath_base, $realpath_query, $realpath_filelist, $realpath_replace_log;

	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param object $main textreplaceのメインオブジェクト
	 */
	public function __construct( $px, $main ){
		$this->px = $px;
		$this->main = $main;

		$this->realpath_base = $this->px->fs()->get_realpath( $this->px->get_path_docroot().$this->px->get_path_controot() );
		$this->realpath_query = $this->main->get_realpath_query();
		$this->realpath_filelist = $this->main->get_realpath_filelist();
		$this->realpath_replace_log = $this->main->get_realpath_log();
	}

	/**
	 * 検索を実行する
	 * @param array $query 検索クエリ
	 * @return object replacerオブジェクト
	 */
	public function replace( $query ){
		$this->query = $query;

		$this->log( '====================================' );
		$this->log( 'px2-textreplace start' );
		$this->log( @date('Y-m-d H:i:s') );
		$this->log( json_encode( $this->query, JSON_PRETTY_PRINT ) );
		$this->log( '------------' );

		// var_dump( $this->px->get_path_homedir() );


		// ファイルリストを作成する
		// $options['charset'] は、保存されているCSVファイルの文字エンコードです。
		// 省略時は UTF-8 から、内部エンコーディングに変換します。

		$path = $this->realpath_filelist;
		$path = $this->px->fs()->localize_path($path);

		if( !$this->px->fs()->is_file( $path ) ){
			// ファイルがなければ終了
			return $this;
		}

		$fp = fopen( $path, 'r' );
		if( !is_resource( $fp ) ){
			return $this;
		}

		$idx = 0;
		$defs = array();
		while( $SMMEMO = fgetcsv( $fp , 10000 , ',' , '"' ) ){
			foreach( $SMMEMO as $key=>$row ){
				$SMMEMO[$key] = mb_convert_encoding( $row , mb_internal_encoding() , 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
			}
			if( $idx === 0 ){
				foreach( $SMMEMO as $key=>$row ){
					array_push($defs, $SMMEMO[$key]);
				}
			}else{
				$row = array();
				foreach( $SMMEMO as $key=>$csv_row ){
					$row[$defs[$key]] = $SMMEMO[$key];
				}
				$this->replace_file( $row );
			}
			$idx ++;
			continue;
		}
		fclose($fp);

		$this->log( '------------' );
		$this->log( @date('Y-m-d H:i:s') );
		$this->log( 'exit;' );
		$this->log( "\n" );
		$this->log( "\n" );

		return $this;
	}


	/**
	 * ファイル内容を検索し、置換して保存しなおす
	 */
	private function replace_file( $row ){
		$realpath_file = $this->px->fs()->get_realpath($this->realpath_base.$row['path']);
		if( !$this->px->fs()->is_file( $realpath_file ) ){ return false; }

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
			$bin = preg_replace( $regexp, $this->query['replace_str'], $bin, -1, $count );
		}catch( Exception $e ){
			return false;
		}

		if( !$this->px->fs()->save_file( $realpath_file, $bin ) ){
			return false;
		}

		$this->log( $row['path'].' ('.$count.')' );

		return true;
	}

	/**
	 * ログを保存する
	 */
	private function log( $msg ){
		$rtn = error_log( trim($msg)."\n", 3, $this->realpath_replace_log );
		return $rtn;
	}

	/**
	 * 検索結果を取得する
	 */
	public function get_results(){
		$query = json_decode( $this->px->fs()->read_file( $this->realpath_query ) );
		$csv = $this->px->fs()->read_csv( $this->realpath_filelist );
		$csv = (new \tomk79\csv2json( $this->realpath_filelist ))->fetch_assoc();

		return array( 'query'=>$query, 'results'=>$csv );
	}

}