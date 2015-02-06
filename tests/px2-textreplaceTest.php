<?php
/**
 * Test for tomk79\px2-textreplace
 * 
 * $ cd (project dir)
 * $ ./vendor/phpunit/phpunit/phpunit tests/px2-textreplaceTest.php px2TextReplace
 */

class px2TextReplaceTest extends PHPUnit_Framework_TestCase{

	/**
	 * ファイルシステムユーティリティ
	 */
	// private $fs;

	/**
	 * setup
	 */
	public function setup(){
		// $this->fs = new \tomk79\filesystem();
	}

	/**
	 * 疎通確認テスト
	 */
	public function testPing(){

		// ping打ってみる
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.ping' ,
		] );

		$this->assertTrue( trim($output) == 'ok' );

		// 後始末
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=clearcache' ,
		] );

	}//testPing()

	/**
	 * 検索するテスト
	 */
	public function testSearch(){

		// 文字列検索
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.search&q='.urlencode('PX=publish.run').'&target_contents=1&contents_region='.urlencode('/') ,
		] );

		$json = json_decode( $output );
		$this->assertEquals( 'PX=publish.run', $json->query->q );
		$this->assertTrue( $json->query->target_contents );
		$this->assertFalse( $json->query->q_case_strict );
		$this->assertEquals( 1, count($json->results) );

		// 文字列検索(大文字小文字を区別)
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.search&q='.urlencode('DIV').'&target_contents=1&contents_region='.urlencode('/').'&q_case_strict=1' ,
		] );

		$json = json_decode( $output );
		// var_dump($json);
		$this->assertEquals( 'DIV', $json->query->q );
		$this->assertTrue( $json->query->target_contents );
		$this->assertTrue( $json->query->q_case_strict );
		$this->assertEquals( 0, count($json->results) );

		// 文字列検索(大文字小文字を区別)
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.search&q='.urlencode('<div').'&target_contents=1&contents_region='.urlencode('/').'&q_case_strict=1' ,
		] );

		$json = json_decode( $output );
		// var_dump($json);
		$this->assertEquals( '<div', $json->query->q );
		$this->assertTrue( $json->query->target_contents );
		$this->assertTrue( $json->query->q_case_strict );
		$this->assertEquals( 13, count($json->results) );

		// 後始末
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=clearcache' ,
		] );

	}//testSearch()


	/**
	 * 置換するテスト
	 */
	public function testReplace(){
		$tmp_path_logfile = __DIR__.'/testData/standard/px-files/_sys/ram/data/textreplace_logs/replace_log_'.@date('Ymd').'.log';


		// 文字列置換
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.replace&q='.urlencode('<div').'&target_contents=1&contents_region='.urlencode('/').'&replace_str='.urlencode('<DIV') ,
		] );

		$json = json_decode( $output );
		$this->assertEquals( '<div', $json->query->q );
		$this->assertTrue( is_file( $tmp_path_logfile ) );
		$this->assertFalse( $json->query->q_case_strict );
		$this->assertEquals( 13, count($json->results) );

		// 文字列検索(大文字小文字を区別)
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.search&q='.urlencode('<div').'&target_contents=1&contents_region='.urlencode('/').'&q_case_strict=1' ,
		] );

		$json = json_decode( $output );
		// var_dump($json);
		$this->assertEquals( '<div', $json->query->q );
		$this->assertEquals( 0, count($json->results) );



		// 文字列置換
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.replace&q='.urlencode('<DIV').'&target_contents=1&contents_region='.urlencode('/').'&q_case_strict=1&replace_str='.urlencode('<div') ,
		] );

		$json = json_decode( $output );
		// var_dump($json);
		$this->assertEquals( '<DIV', $json->query->q );
		$this->assertTrue( is_file( $tmp_path_logfile ) );
		$this->assertTrue( $json->query->q_case_strict );
		$this->assertEquals( 13, count($json->results) );

		// 文字列検索(大文字小文字を区別)
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=textreplace.search&q='.urlencode('<div').'&target_contents=1&contents_region='.urlencode('/').'&q_case_strict=1' ,
		] );

		$json = json_decode( $output );
		// var_dump($json);
		$this->assertEquals( '<div', $json->query->q );
		$this->assertEquals( 13, count($json->results) );


		// var_dump( file_get_contents( $tmp_path_logfile ) );

		// 後始末
		$output = $this->passthru( [
			'php',
			__DIR__.'/testData/standard/.px_execute.php' ,
			'/?PX=clearcache' ,
		] );
		// ログファイルを消す
		unlink( $tmp_path_logfile );
		rmdir( dirname($tmp_path_logfile) );

	}//testReplace()




	/**
	 * コマンドを実行し、標準出力値を返す
	 * @param array $ary_command コマンドのパラメータを要素として持つ配列
	 * @return string コマンドの標準出力値
	 */
	private function passthru( $ary_command ){
		$cmd = array();
		foreach( $ary_command as $row ){
			$param = '"'.addslashes($row).'"';
			array_push( $cmd, $param );
		}
		$cmd = implode( ' ', $cmd );
		ob_start();
		passthru( $cmd );
		$bin = ob_get_clean();
		return $bin;
	}// passthru()

}
