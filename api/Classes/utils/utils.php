<?php

class Utils{
  function __construct(){
  }
  public function return_jsonobj_from_string($str){
      if(is_string($str)){
          $json = json_decode($str, true);
          return $json;
      }else{
          return $str;
      }
  }
  public function referer_check(){
    $ref = @$_SERVER['HTTP_REFERER'];
    $OKREFS = array(@$_SERVER['SERVER_NAME']);
    $flg = False;
    foreach($OKREFS as $idx => $okref){
      $flg = ($okref == '') ? True : $flg;
      $flg = ($okref == '') ? True : (strstr($ref, $okref) ? True : $flg);
    }
    return $flg;
  }
  public function get_browser_language(){
    $server_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $languages = explode(',', $server_lang);
    
    #  get most high priority language
    $most_prio_lang  = 'English';
    $most_prio_value = -9999;
    foreach ($languages as $language) {
      $lang_prio = explode(';', $language);
      $l = $lang_prio[0];
      $p = (isset($lang_prio[1])) ? (float) str_replace('q=','',$lang_prio[1]) : 1;
      $most_prio_lang  = ($p > $most_prio_value) ? $l : $most_prio_lang;
      $most_prio_value = ($p > $most_prio_value) ? $p : $most_prio_value;
    }
    
    #  translate it to intuitive strings
    $result = 'English';
    if (preg_match('/^ja/i', $most_prio_lang)) {
      $result = 'Japanese';
    } elseif (preg_match('/^en/i', $most_prio_lang)) {
      $result = 'English';
    } elseif (preg_match('/^zh/i', $most_prio_lang)) {
      $result = 'Chinese';
    }
    
    return $result;
    #return 'Japanese';
  }
  public function output_json_result($result, $argobj){
    header('Content-type: text/javascript; charset=utf-8');
    if(@$argobj['callback'] != ''){
      echo $argobj['callback']."(".json_encode($result).")";
    }else{
      echo json_encode($result);
    }
  }
  public function output_csv($uid, $result, $fname){
    # read user config file...
    require_once getcwd().'/.gd.confg.php';
    if(file_exists(getcwd().'/.user/'.$uid)){
      require_once getcwd().'/.user/'.$uid;
    }

    require_once getcwd().'/Classes/ess/ess.php';
    $OUT_FILT_CHARCODE = (new Ess())->return_userconfig($uid, '', 'charcode');

    $ext = 'csv';
    
    $TMP_DIRR = '/tmp/';
    $tmpfname = uniqid($uid.$fname);
    $tmpfpath = $TMP_DIRR.$tmpfname;
    
    touch($tmpfpath);
    $fp=fopen($tmpfpath,'r+');
 
    foreach($result as $v){
      $newrow = array();
      foreach($v as $cell){
        $newcell = $cell;
        $newcell = preg_replace('/^"/', '', $newcell);
        $newcell = preg_replace('/"$/', '', $newcell);
        array_push($newrow, $newcell);
      }
      fputcsv($fp,$newrow,',','"');
    }
    rewind($fp);
    $csv=stream_get_contents($fp);
    fclose($fp);
    $file_name = (strstr($fname, '.')) ? $fname : $fname.".".$ext;
    
    
    #it works with safari
    $this->set_headers_to_download($file_name);
    if($OUT_FILT_CHARCODE != 'UTF-8'){
      if($OUT_FILT_CHARCODE == 'SJIS'){
        echo mb_convert_encoding($csv,"SJIS-win", "UTF-8");
      }else{
        echo mb_convert_encoding($csv,$OUT_FILT_CHARCODE, "UTF-8");
      }
    }else{
      echo $csv;
    }
    
    unlink($tmpfpath);
  }
  public function set_headers_to_download($fname){
    header('Content-Type:text/plain');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
  }
  public function load_userconfig_assign($argobj){
    require_once getcwd().'/.gd.confg.php';
    require_once getcwd().'/Classes/ess/ess.php';
    $ess = new Ess();
    $argobj['timezone'] = $ess->return_userconfig(@$argobj['uid'], @$argobj['lid'], 'timezone');
    $argobj['charcode'] = $ess->return_userconfig(@$argobj['uid'], @$argobj['lid'], 'charcode');
#    $argobj['mindate'] =  @$MINDATE;

# --
#    global $TIME_ZONE, $OUT_FILT_CHARCODE;
#    $ufpath = getcwd().'/.user/'.@$argobj['uid'];
#    $argobj['timezone'] = $TIME_ZONE;
#    $argobj['charcode'] = $OUT_FILT_CHARCODE;
#    if(file_exists($ufpath)){
#      require_once $ufpath;
#      global $UTIMEZONE, $OUT_FILT_CHARCODE;
#      $argobj['timezone'] = isset($UTIMEZONE) ? $UTIMEZONE : $TIME_ZONE;
#      $argobj['charcode'] = $OUT_FILT_CHARCODE;
#      $argobj['mindate'] =  @$MINDATE;
#    }
    return $argobj;
  }
  public function return_cleanup_row($row){
    $newrow = array();
    foreach($row as $idx => $cell){
      array_push($newrow,  mb_convert_encoding(str_replace('"','', $cell), 'UTF-8', 'UTF-8') );
    }
    return $newrow;
  }
  public function return_val_comma_label($val){
      if($val == '-'){
          return $val;
      }
      $withcomma = number_format($val);
      $num_split_3digi = split(',', $withcomma);
      $len_dig = count($num_split_3digi);
      $val = $withcomma;
      $unit = '';
      if($len_dig == 2){
          $unit = 'K';
      }elseif($len_dig == 3){
          $unit = 'M';
      }elseif($len_dig >= 4){
          $unit = 'B';
      }
      if($unit != ''){
          $val = $num_split_3digi[0];
          $dec = substr($num_split_3digi[1], 0, 2);
          if($len_dig > 4){
              $pos_idx = $len_dig - 4;
              $dec_idx = $pos_idx + 1;
              $cnt = 1;
              while($cnt <= $pos_idx){
                  $val = $val.','.$num_split_3digi[$cnt];
                  $cnt++;
              }
              $dec = substr($num_split_3digi[$dec_idx], 0, 2);
          }
          $val = $val.'.'.$dec;
      }else{
          $val = $withcomma;
      }
      $result = $val.$unit;
  
      return $result;
  }


  public function translate_week($date){
    $WEEK_TRANSLATE = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    return @$WEEK_TRANSLATE[$date];
  }
  public function wordsdict(){
    $lang = $this->get_browser_language();
    $singles = array(
      'path'           => ($lang == 'Japanese') ? 'パス' : 'Path',
      'cj'             => ($lang == 'Japanese') ? 'カスタマージャーニー' : 'CJ',
      'cj-full'        => ($lang == 'Japanese') ? 'カスタマージャーニー' : 'Customer Journey',
      'pv'             => ($lang == 'Japanese') ? 'イベント' : 'Event',
      'depth'          => ($lang == 'Japanese') ? 'イベント' : 'Event',
      'ev'             => ($lang == 'Japanese') ? 'イベント' : 'Event',
      'ss'             => ($lang == 'Japanese') ? 'セッション' : 'Session',
      'uu'             => ($lang == 'Japanese') ? 'UU' : 'Unique Users',
      'cnt'            => ($lang == 'Japanese') ? '数' : ' Count',
      'imp'            => ($lang == 'Japanese') ? 'インプ' : 'Imp',
      'days'           => ($lang == 'Japanese') ? '日' : 'Day',
      'advlog'         => ($lang == 'Japanese') ? '広告ログ' : 'Advertise Log',
      'weblog'         => ($lang == 'Japanese') ? 'サイトログ' : 'Website Log',
      'advlog-short'   => ($lang == 'Japanese') ? '広告' : 'Adv',
      'weblog-short'   => ($lang == 'Japanese') ? 'サイト' : 'Web',
      'total'          => ($lang == 'Japanese') ? '総数' : 'Total',
      'domain'         => ($lang == 'Japanese') ? 'ドメイン' : 'Domain',
      'page'           => ($lang == 'Japanese') ? 'ページ' : 'Page',
      'pagename'       => ($lang == 'Japanese') ? 'ページ名' : 'Page Name',
      'all'            => ($lang == 'Japanese') ? '全て' : 'All',
      'camp'           => ($lang == 'Japanese') ? 'キャンペーン' : 'Campaign',
      'media'          => ($lang == 'Japanese') ? 'メディア' : 'Media',
      'adname'         => ($lang == 'Japanese') ? 'アドネーム' : 'Adname / Purchased Keywords',
      'placement'      => ($lang == 'Japanese') ? 'プレースメント' : 'Placement',
      'device'         => ($lang == 'Japanese') ? 'デバイス' : 'Device',
      'ip'             => ($lang == 'Japanese') ? 'IP' : 'IP',
      'site'           => ($lang == 'Japanese') ? 'サイト' : 'Site',
      'skey'           => ($lang == 'Japanese') ? '検索キー' : 'search words',
      'enttype'        => ($lang == 'Japanese') ? 'インタラクションタイプ' : 'InteractionType',
      'sengine'        => ($lang == 'Japanese') ? 'サーチエンジン' : 'search engine',
      'entry'          => ($lang == 'Japanese') ? '流入' : 'entry',
      'direct'         => ($lang == 'Japanese') ? '直接' : 'Direct',
      'noquery'        => ($lang == 'Japanese') ? '絞り込みなし' : 'All',
      'forward'        => ($lang == 'Japanese') ? 'First' : 'First',
      'backward'       => ($lang == 'Japanese') ? 'Last' : 'Last',
'tillcv'         => ($lang == 'Japanese') ? 'CV直前まで' : 'Till 1st cv',
'tillend'        => ($lang == 'Japanese') ? '最後まで表示' : 'Till end',
      'overmaxmessage' => ($lang == 'Japanese') ? 
                          'チャート描画件数の上限を超えました。全件ご確認頂く場合にはcsvダウンロードをご利用下さい。' : 
                          'It exceeds the limit of drawing charts. Please download as csv if you want to see the whole data.',
      '/'              => ($lang == 'Japanese') ? '・' : ' / ',
      '-'              => ($lang == 'Japanese') ? '～' : '-',
      'cumu'           => ($lang == 'Japanese') ? '累積' : 'Cumulative',
      'non'            => ($lang == 'Japanese') ? '非' : 'Non-',
      'rate'           => ($lang == 'Japanese') ? '率' : 'Rate',
      'ratio'          => ($lang == 'Japanese') ? '率' : 'Ratio',
      'cv'             => ($lang == 'Japanese') ? 'CV' : 'CV',
      'cv-full'        => ($lang == 'Japanese') ? 'CV' : 'conversions',
      'useg'           => ($lang == 'Japanese') ? 'セグメント' : 'User Segment',
      'spacebtwwords'  => ($lang == 'Japanese') ? '' : ' ',
      'plural'         => ($lang == 'Japanese') ? '' : 's',
      'tailrate'       => ($lang == 'Japanese') ? '表示上限以上のCVユーザーの割合' : 'CV User Ratio Above the Chart',
      'max'            => ($lang == 'Japanese') ? '最大' : 'Max',
      'min'            => ($lang == 'Japanese') ? '最小' : 'Min',
      'avg'            => ($lang == 'Japanese') ? '平均' : 'Avg',
      'freq'           => ($lang == 'Japanese') ? '頻出' : 'Freq',
      'most'           => ($lang == 'Japanese') ? '最大' : 'Most',
      'to'             => ($lang == 'Japanese') ? 'までの' : 'to',
      'click'          => ($lang == 'Japanese') ? 'クリック' : 'Click',
      'listing'        => ($lang == 'Japanese') ? 'リスティング' : 'Listing',
      'banner'         => ($lang == 'Japanese') ? 'バナー' : 'Banner',
      'organic'        => ($lang == 'Japanese') ? '自然検索' : 'Organic',
      'ref'            => ($lang == 'Japanese') ? '参照元' : 'Referer',
      'apply'          => ($lang == 'Japanese') ? '適用' : 'Apply',
      'cancel'         => ($lang == 'Japanese') ? '取り消し' : 'Cancel',
      'close'          => ($lang == 'Japanese') ? '閉じる' : 'CLOSE',
      'pageprev'       => ($lang == 'Japanese') ? '前のトップ' : 'Previous Top ',
      'pagenext'       => ($lang == 'Japanese') ? '次のトップ' : 'Next Top ',
      'dateformat-yy'  => ($lang == 'Japanese') ? '年' : '-',
      'dateformat-mm'  => ($lang == 'Japanese') ? '月' : '-',
      'dateformat-dd'  => ($lang == 'Japanese') ? '日' : '',
      'dateformat-pd'  => ($lang == 'Japanese') ? '日遡る' : 'days',
      'dateselect-d'   => ($lang == 'Japanese') ? '日' : 'd',
      'dateselect-d-l' => ($lang == 'Japanese') ? '日次指定' : 'daily',
      'dateselect-m'   => ($lang == 'Japanese') ? '月' : 'm',
      'dateselect-m-l' => ($lang == 'Japanese') ? '月次指定' : 'monthly',
      'dateselect-c'   => ($lang == 'Japanese') ? 'c' : 'c',
      'dateselect-c-l' => ($lang == 'Japanese') ? 'キャッシュ' : 'cache',
      'monthformat-yy' => ($lang == 'Japanese') ? '年' : '-',
      'monthformat-mm' => ($lang == 'Japanese') ? '月' : '',

      
    );
    $combinations = array(
      'cvcumu'    => @$singles['cv'].@$singles['spacebtwwords'].@$singles['cumu'].@$singles['spacebtwwords'].@$singles['ratio'],
      'noncvcumu' => @$singles['non'].@$singles['cv'].@$singles['spacebtwwords'].@$singles['cumu'].@$singles['spacebtwwords'].@$singles['ratio'],
      'cvuurate'      => @$singles['cv'].@$singles['spacebtwwords'].@$singles['uu'].@$singles['spacebtwwords'].@$singles['rate'],
      'cvuucnt'       => @$singles['cv'].@$singles['spacebtwwords'].@$singles['uu'].@$singles['spacebtwwords'].@$singles['cnt'],
      'cvabs'         => @$singles['cv'].@$singles['spacebtwwords'].@$singles['uu'].@$singles['spacebtwwords'].@$singles['cnt'],
      'noncvuucnt'    => @$singles['non'].@$singles['cv'].@$singles['spacebtwwords'].@$singles['uu'].@$singles['spacebtwwords'].@$singles['cnt'],
      'mostfreq'      => @$singles['most'].@$singles['spacebtwwords'].@$singles['freq'],
      'evtocv'        => ($lang == 'Japanese') 
                         ? @$singles['cv'].@$singles['to'].@$singles['ev'].@$singles['cnt']
                         : @$singles['ev'].@$singles['plur'].@$singles['spacebtwwords'].@$singles['to'].@$singles['spacebtwwords'].@$singles['cv'],
      'cvlenhead'     => ($lang == 'Japanese') ? @$singles['cv'].@$singles['uu'] : 'converted after the',
      'cvlentail'     => ($lang == 'Japanese') ? @$singles['ev'].'で'.@$singles['cv'] : 'event encontered',
      'cvpathtitle'   => @$singles['cj-full'].@$singles['spacebtwwords'].@$singles['path'].@$singles['plur'],
      'advlogdetail'  => ($lang == 'Japanese') ?  @$singles['advlog'].'内訳' : @$singles['advlog'],
      'weblogdetail'  => ($lang == 'Japanese') ?  @$singles['weblog'].'内訳' : @$singles['weblog'],
      'uucnt'         => @$singles['uu'].@$singles['spacebtwwords'].@$singles['cnt'],
      'evcnt'         => @$singles['ev'].@$singles['spacebtwwords'].@$singles['cnt'],
      'domainpage'    => @$singles['domain'].@$singles['/'].@$singles['page'],
      'campaignmedia' => @$singles['camp'].@$singles['/'].@$singles['media'],
      'refdomain'     => @$singles['ref'].@$singles['spacebtwwords'].@$singles['domain'],
      'entrypage'     => @$singles['entry'].@$singles['spacebtwwords'].@$singles['page'],
      'directentry'   => @$singles['direct'].@$singles['spacebtwwords'].@$singles['entry'],
      'uploaddeffile' => ($lang == 'Japanese') ? '定義ファイルアップロード' : 'Upload Definition File',
      'content-titles' => array(
        'widget-summ'            => ($lang == 'Japanese') ? 'グローバルサマリー' : 'Stats & Trends',
        'widget-summ-short'      => ($lang == 'Japanese') ? 'サマリー' : 'basic analysis',
        'widget-path'            => ($lang == 'Japanese') ? 'カスタマージャーニー' : 'Customer Journeys',
        'widget-path-short'      => ($lang == 'Japanese') ? 'パスパターン' : 'cj path',
        'summ'                   => ($lang == 'Japanese') ? '概要' : 'Summary',
        'logCountTrend'          => ($lang == 'Japanese') ? 'トレンド' : 'Trends',
        'logCountTrendMultiUser' => ($lang == 'Japanese') ? '比較トレンド' : 'Comparison Trends',
        'distFreq'               => ($lang == 'Japanese') ? '頻度分布' : 'Frequency Dist',
        'distFreqPV'             => ($lang == 'Japanese') ? 'CV数とCVR' : 'CV & CV Rate',
        'pieBrowsers'            => ($lang == 'Japanese') ? 'デバイス内訳' : 'Device Detail',
        'beforeAfterTable'       => ($lang == 'Japanese') ? 'CV前後比較' : 'Before/After CV',
        'detailTableEventCount'  => ($lang == 'Japanese') ? 'ランキング' : 'Ranking',
        'detailTableEntry'       => ($lang == 'Japanese') ? '流入解析' : 'Entry',
        'detailTableRegionMap'   => ($lang == 'Japanese') ? '地域' : 'By Region',
        'attrScore'              => ($lang == 'Japanese') ? 'アトリビューション' : 'Attribution Score',
        'attrScoreFMD'           => ($lang == 'Japanese') ? 'F-M-Lテーブル' : 'F-M-L Table',
      ),
      'table-header' => array(
        'interactionType' => ($lang == 'Japanese') ? 'インタラクションタイプ' : 'Interaction Type',
        'score'           => ($lang == 'Japanese') ? 'スコア' : 'Score',
        'site'            => @$singles['site'],
        'before-event'    => ($lang == 'Japanese') ? 'CV前イベント' : 'Event Before CV',
        'before-rate'     => ($lang == 'Japanese') ? 'CV前率' : 'Rate Before CV',
        'after-event'     => ($lang == 'Japanese') ? 'CV後イベント' : 'Event After CV',
        'after-rate'      => ($lang == 'Japanese') ? 'CV後率' : 'Rate After CV',
        'event'           => @$singles['ev'].@$singles['spacebtwwords'].@$singles['cnt'],
        'uu'              => @$singles['uu'].@$singles['spacebtwwords'].@$singles['cnt'],
        'count'           => @$singles['cnt'],
        'ratio'           => @$singles['ratio'],
      ),
      'table-labels' => array(
        0  => ($lang == 'Japanese') ? 'その他'           : 'other',
        1  => ($lang == 'Japanese') ? 'imp'              : 'imp',
        2  => ($lang == 'Japanese') ? 'click'            : 'click',
        3  => ($lang == 'Japanese') ? 'listing'          : 'sz listing',
        10 => ($lang == 'Japanese') ? '自然検索'         : 'organic',
        12 => ($lang == 'Japanese') ? '広告クリック'     : 'ad click',
        13 => ($lang == 'Japanese') ? 'リファラあり'     : 'referrer',
        14 => ($lang == 'Japanese') ? 'リスティング流入' : 'listing',
        15 => ($lang == 'Japanese') ? '直接流入'         : 'direct'
      ),
      'content-options' => array(
        'xAxis' => array(
          'byminute'    => ($lang == 'Japanese') ? '分刻み' : 'by minute',
          'dailyhourly' => ($lang == 'Japanese') ? '時間刻み' : 'daily hourly',
          'daily'       => ($lang == 'Japanese') ? '日次' : 'daily',
          'weekly'      => ($lang == 'Japanese') ? '週次' : 'weekly',
          'monthly'     => ($lang == 'Japanese') ? '月次' : 'monthly',
          'hourly'      => ($lang == 'Japanese') ? '時間帯別' : 'hourly',
          'byday'       => ($lang == 'Japanese') ? '曜日別' : 'by day',
          'bin-days'    => ($lang == 'Japanese') ? '日数' : 'days',
        ),
        'yAxis' => array(
          'periodonly'  => ($lang == 'Japanese') ? '指定期間' : 'not include prev',
          'includeprev' => ($lang == 'Japanese') ? '指定期間＋遡り期間' : 'include prev',
          'absvalue'    => ($lang == 'Japanese') ? '数' : 'abs',
          'cumuvalue'   => ($lang == 'Japanese') ? '累積' : 'cumu',
        ),
        'viewmax' => ($lang == 'Japanese') ? '表示上限' : 'below',
        'showtop' => ($lang == 'Japanese') ? '上位表示' : 'show top'
      ),
      'attr-models' => array(
        'lastclick'     => ($lang == 'Japanese') ? 'ラストクリック' : 'last click',
        'lastclick2'    => ($lang == 'Japanese') ? '仮想クリック有りラストクリック' : 'last click 2',
        'clickonly'     => ($lang == 'Japanese') ? 'クリック均等分配' : 'click only',
        'clickonly2'    => ($lang == 'Japanese') ? '仮想クリック有り均等分配' : 'click only 2',
        'clickonlyseo'  => ($lang == 'Japanese') ? '自然検索も含むクリック均等分配' : 'click only seo',
        'clickonlyseo2' => ($lang == 'Japanese') ? '自然検索も仮想クリックも含む均等分配' : 'click only seo2',
        'ctrwgt'        => ($lang == 'Japanese') ? 'CTR重み付け均等分配' : 'ctr wgt',
        'ctrwgtseo'     => ($lang == 'Japanese') ? '自然検索有りCTR重みづけ均等配分' : 'ctr wgt seo',
        'firstclick'    => ($lang == 'Japanese') ? '初回クリック' : 'first click',
        'firstclick2'   => ($lang == 'Japanese') ? '仮想クリック有り初回クリック' : 'first click 2',
      )
    );

    $toui = array(
      'toui-contents' => array(
        'apply'    => $singles['apply'],
        'cancel'   => $singles['cancel'],
        'close'    => $singles['close'],
        'pageprev' => $singles['pageprev'],
        'pagenext' => $singles['pagenext'],
        # cv-path
        'pattern_cv'     => ($lang == 'Japanese') ? 'CVパターン数' : 'CV Patterns',
        'pattern_total'  => ($lang == 'Japanese') ? '総パターン数' : 'Total Patterns',
        'samplelogtitle' => ($lang == 'Japanese') ? 'サンプルユーザーログ' : 'SAMPLE USER PATH LOG',
        'opt-cvpath' => array(
          'any' => array(
            'label'       => ($lang == 'Japanese') ? '全範囲' : 'any',
            'description' => ($lang == 'Japanese') ? '全タッチポイントを検索' : 'search all touch point by keywords',
          ),
          '1st' => array(
            'label'       => ($lang == 'Japanese') ? '先頭のみ' : '1st',
            'description' => ($lang == 'Japanese') ? 'パスの先頭を対象に検索' : 'search only first touch points by keywords',
          ),
          'last' => array(
            'label'       => ($lang == 'Japanese') ? '最後のみ' : 'last',
            'description' => ($lang == 'Japanese') ? 'パスの最後を対象に検索' : 'search only last touch points by keywords',
          )
        ),
        'guide_cvpath' => array(
          'filtbuild'   => ($lang == 'Japanese') ? '' : '',
          'filtsearch'  => ($lang == 'Japanese') ? '' : '',
          'tpdepth'     => ($lang == 'Japanese') ? '' : '',
          'download'    => ($lang == 'Japanese') ? 'ダウンロードして全てのランキングを見る' : 'download all path-pattern ranking',
          'paginginput' => ($lang == 'Japanese') ? '頁番号を入力+Enterキーで遷移します。' : 'input page numbe and click enter.',
          'pagemax'     => ($lang == 'Japanese') ? '最大頁数' : 'max page',
          'rankfromto'  => ($lang == 'Japanese') ? '現在表示中のランキング' : 'Ranking number currently shown.'
        ),
        'each_path_popup' => array(
          'title'      => ($lang == 'Japanese') ? 'パス詳細' : 'PATH DETAIL',
          'close'      => ($lang == 'Japanese') ? '閉じる' : 'CLOSE',
          'apply'      => ($lang == 'Japanese') ? '適用' : 'Apply',
          'beforecvpv' => ($lang == 'Japanese') ? 'CV前PV' : 'before CV pv',
          'cvrate'     => ($lang == 'Japanese') ? 'CV率' : 'CV rate',
          'cvuser'     => ($lang == 'Japanese') ? 'CV UU' : 'cv user',
          'noncvuser'  => ($lang == 'Japanese') ? '非CV UU' : 'non-cv user'
        ),
        'title_essamountviewer' => array(
          'title' => array(
            'arrow'   => ($lang=='Japanese') ? '詳細を開く' : 'Open to details',
            'UU'      => ($lang=='Japanese') ? 'ユニークユーザー数' : 'Unique Users',
            'Event'   => ($lang=='Japanese') ? 'イベント数' : 'Event',
            'in'      => ($lang=='Japanese') ? ' (' : ' in ',
            'in_tail' => ($lang=='Japanese') ? ')' : '',
            'segments' => ($lang=='Japanese') ? array('合計','ユーザーセグメント','CV') : array('Total','User Segment','CV')
          )
        )
      ),
      'toui-ess' => array(
        'ess_status' => array(
          'memoristat'  => ($lang == 'Japanese') ? 'メモリ使用状況' : 'memory status',
          'udbdstatus' => array(
            'ok'     => ($lang == 'Japanese') ? '正常' : 'OK',
            'fail'   => ($lang == 'Japanese') ? '問題' : 'ERROR',
            'GREEN'  => ($lang == 'Japanese') ? '正常' : 'OK',
            'YELLOW' => ($lang == 'Japanese') ? '注意' : 'OK',
            'ORANGE' => ($lang == 'Japanese') ? '危険' : 'WARNING',
            'ERROR'  => ($lang == 'Japanese') ? '問題' : 'ERROR'
          )
        ),
        'ess_startup' => array(
          'instance' => array(
            'insttype' => ($lang == 'Japanese') ? 'インスタンスタイプ' : 'Instance Type',
            'instnum'  => ($lang == 'Japanese') ? 'インスタンス個数'   : 'Instance Number',
            'createInstance' => ($lang == 'Japanese') ? 'インスタンスを作成して開始' : 'Start with worker nodes',
            'startWithLocal' => ($lang == 'Japanese') ? 'スタート' : 'Start',
            'terminateInstance' => ($lang == 'Japanese') ? 'インスタンスを削除' : 'Terminate Instance',
            'whyinstance'    =>  ($lang == 'Japanese') 
              ? 'このコンテンツをご覧になる為にはAWSのインスタンスをセットアップしてください。' 
              : 'To start this contents, please set up AWS instances.',
            'takeover' =>  ($lang == 'Japanese') ? 'ユーザー切替' : 'Take over the other user!',
            'nowisviewmode' =>  ($lang == 'Japanese') ? '閲覧モード実行中です。' : 'View Mode.',
            'startviewmode' =>  ($lang == 'Japanese') ? '閲覧モードで開く' : 'Start View Mode.',
            'warningduringimporting' =>  ($lang == 'Japanese') 
              ? '現在他のユーザーがインポートプロセスを実行中です。後ほどお試しください。' 
              : 'Currently, the other user is in the importing process, please try this again later.',
            'localused' =>  ($lang == 'Japanese') 
              ? 'ローカル環境は既に他ユーザーが使用しております。ローカル環境以外をご利用下さい。' 
              : 'Local environment is already used. You cannot select "local"',
            'localusedandrunningprocess' =>  ($lang == 'Japanese') 
              ? '現在他のユーザーが作業中です。' 
              : 'The other user is running process.',
            'workingdirused' =>  ($lang == 'Japanese') 
              ? '現在他のユーザーがご利用中です。' 
              : 'Your account is currently used by another user.',
            'workingdirusedandrunningprocess' =>  ($lang == 'Japanese') 
              ? '現在他のユーザーが作業中です。' : 'The other user is running process.',
            'elapsedtime' => ($lang == 'Japanese') ? '起動時間' : 'time',
            'memoristat'  => ($lang == 'Japanese') ? 'メモリ使用状況' : 'memory status',
            'udbdstatus' => array(
              'ok'   => ($lang == 'Japanese') ? '正常' : 'OK',
              'fail' => ($lang == 'Japanese') ? '失敗' : 'FAIL',
            ),
            'estimatedtime' =>  ($lang == 'Japanese') ? '推定時間' : 'estimated time',
            'estimatedtimeterminate' =>  ($lang == 'Japanese') ? '1分' : '1min',
            'estimatedtimecreate' => ($lang == 'Japanese') ? '5分' : '5min',
            'thankyouforwaiting' => ($lang == 'Japanese') ? 'もう少々お待ち下さい。': '',
            'duringoperation' => array(
               'terminating' => ($lang == 'Japanese') ? 'インスタンス削除中です。': 'terminating instances.',
               'creating'    => ($lang == 'Japanese') ? '準備中です。': 'setting up...',
            ),
            'waiteoverestimate' => ($lang == 'Japanese') ? '推定時刻よりも時間がかかっているようです。もう少々お待ち下さい。' : '',
            'confmssg' => array(
              'create'    =>  ($lang == 'Japanese') 
                ? 'インスタンスを開始致しますか？\nインスタンスを開始すると課金が開始されます。' 
                : 'Are you sure to start instance?\nInstances costs while it is running.',
              'terminate' =>  ($lang == 'Japanese') 
                ? 'インスタンスを削除しても宜しいでしょうか？' 
                : 'Are you sure to terminate instance?',
              'leave'     => ($lang == 'Japanese') 
                ? 'インスタンスはまだ課金されている状態です。\n本当にこのページを離れますか？' 
                : 'Are you sure you want to leave now?'
            ),
            'sessionout' => ($lang == 'Japanese') 
              ? 'セッションがタイムアウトしました。メイン画面のメニューから再度CV Pathを実行して下さい。' 
              : 'Session has timed out.Restart cv path from the menu at the main window.'
          ),
          'import' => array(
            'elapsedtime' => ($lang == 'Japanese') ? '開始時間' : 'time',
            'estimatedtime' =>  ($lang == 'Japanese') ? '推定時間' : 'estimated time',
            'estimatedtimecreate' => ($lang == 'Japanese') ? '10分' : '10min',
            'thankyouforwaiting' => ($lang == 'Japanese') ? 'もう少々お待ち下さい。': '',
            'duringoperation' => array(
              'running' => ($lang == 'Japanese') ? 'データ読み込みを開始致しました。' : 'start loading',
              'runningimport' => ($lang == 'Japanese') ? 'データ読み込み中です。' : 'loading data',
              'runningprofile' => ($lang == 'Japanese') ? 'データ準備中です。' : '...'
            ),
            'waiteoverestimate' => ($lang == 'Japanese') ? '推定時刻よりも時間がかかっているようです。もう少々お待ち下さい。' : ''
          )
        )
      ),
      'toui-etc' => array(
        'fuploader' => array(
          'paneltitle'  => ($lang == 'Japanese') ? 'ファイルアップロードパネル' : 'Upload File',
          'dragcsv'     => ($lang == 'Japanese') ? 'CSVファイルをドラッグして下さい。' : 'Drag a CSV file here',
          'or'          => ($lang == 'Japanese') ? 'もしくは' : 'or',
          'browse'      => ($lang == 'Japanese') ? 'こちら' : 'browse',
          'uploadguide' => ($lang == 'Japanese') ? 'からファイルを選択して下さい。' : 'for a file to upload',
          'uploadbttn'  => ($lang == 'Japanese') ? 'アップロード' : 'upload',
          'upload'      => ($lang == 'Japanese') ? 'アップロード' : 'Upload',
          'uploading'   => ($lang == 'Japanese') ? 'アップロード中です' : 'Uploading',
          'change'      => ($lang == 'Japanese') ? 'ファイル変更' : 'Click to Change',
          'cancel'      => ($lang == 'Japanese') ? 'キャンセル' : 'Cancel'
        ),
        'fupload_status' => array(
          'download' =>  ($lang == 'Japanese') ? 'ダウンロード' : 'download',
          'remove'   =>  ($lang == 'Japanese') ? '削除' : 'remove'
        ),
        'loggedout' => array(
          'loggedout' => ($lang == 'Japanese') ? 'ログアウトしました。タブを閉じて下さい。' : 'logged out. Close the tab.'
        ),
        'global_panel' => array(
          'loadconfirm' => ($lang == 'Japanese') 
             ? 'キャッシュに存在しない選択のため少々時間がかかります。(1時間以上かかることもございます。)本当に実行しますか？' 
             : 'It will take a moment, are you sure you want to do this?',
          'confirm' => array(
            'cacheexst' => ($lang=='Japanese') 
              ? "ご選択の条件のキャッシュが存在します。<br><br>キャッシュの内容で続行しますか？<br>再度計算をしますか？<br>(再度計算を行う場合、時間がかかります。1時間以上かかる場合もございます。)" 
              : "There exists a cache.<br>Do you want to run with cache?<br>Or do you want to re-calculate?",
            'cacheno' => ($lang=='Japanese') 
              ? "キャッシュに存在しない選択のため少々時間がかかります。(1時間以上かかることもございます。)<br>計算を実行しますか？" 
              : "There is no cache file.<br>Do you want to start calculation? (It may take a moment.)"
          ),
          'buttons' => array(
            'apply'          => ($lang == 'Japanese') ? '適用' : 'Apply',
            'runwithcache'   => ($lang == 'Japanese') ? 'キャッシュ読み込み' : 'Run With Cache',
            'recalculation'  => ($lang == 'Japanese') ? '再計算実行' : 'Start Recalculation',
            'runanyway'      => ($lang == 'Japanese') ? '計算実行' : 'Start Calculation',
            'cancel'         => ($lang == 'Japanese') ? 'キャンセル' : 'Cancel'
          )
        ),
        'global_cache' => array(
          'searchbox' => ($lang == 'Japanese') ? 'キーワードで絞り込み' : 'query by keywords'
        ),
        'global_custom' => array(
          'delphys' => ($lang == 'Japanese') ? 'プレースメントIDで絞り込み' : 'query by placement id'
        ),
        'global_daterange' => array(
          'clndto' => ($lang == 'Japanese') ? '～' : '-',
          'dateformat' => ($lang == 'Japanese') ? 'yy/mm/dd' : 'mm/dd/y',
          'firstday' => ($lang == 'Japanese') ? 1 : 0,
          'daterangepicker' => array(
            'format' => ($lang == 'Japanese') ? 'YYYY/MM/DD' : 'MM/DD/YYYY',
            'applyLabel' => ($lang == 'Japanese') ? '設定' : 'Apply',
            'cancelLabel' => ($lang == 'Japanese') ? '取り止め' : 'Cancel',
            'daysOfWeek' => ($lang == 'Japanese') 
              ? array('日','月','火','水','木','金','土')
              : array('Sun','Mon','Tue','Wed','Thu','Fri','Sat'),
            'monthNames' => ($lang == 'Japanese') 
              ? array('1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月')
              : array('January','February','March','April','May','June','July','August','September','October','November','December'),
            'firstDay' => ($lang == 'Japanese') ? 1 : 0
          )

        )
      ),
      'toui-filters' => array(
        'overwrite' => ($lang == 'Japanese') ? '選択したセットで上書きされます' : 'Choose which set to overwrite',
        'morethanlimieinoneset' =>  ($lang == 'Japanese') 
          ? '※ワンセット内での上限数に達しました。' 
          : 'It reached the limit for one set.',
        'view_panel_title' => ($lang == 'Japanese') ? '適用条件一覧' : 'APPLIED CALCULATION SETTING SUMMARY',
        'close' => ($lang == 'Japanese') ? '閉じる' : 'CLOSE',
      )
    );
    return array_merge($singles, $combinations, $toui);
  }
}



?>
