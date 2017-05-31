<?php
require_once(dirname(__FILE__).'/utils/utils.php');
require_once(dirname(__FILE__).'/ess/ess.php');
require_once(dirname(__FILE__).'/ess/essParams.php');
require_once(dirname(__FILE__).'/utils/mysqldb.php');

class GetOptions{
  private $utils, $ess, $params, $lang;
  function __construct(){
    $this->utils  = new Utils();
    $this->ess    = new Ess();
    $this->params = new EssParams();
    $this->lang   = $this->utils->get_browser_language();
  }
  public function get_contents_availability($uid){
    $isPaths   = $this->ess->is_available_for_user($uid);
    $wordsdict = $this->utils->wordsdict();
    $jsonobj = array(
      'data' => array(
        'paths'        => array(
          'isExist' => $isPaths,
          'title'   => @$wordsdict['cvpathtitle'],
          'path'    => '/paths'
        )
      )
    );
    return $jsonobj;
  }
  public function get_global($uid, $lid, $mindate){
    $glbl = new GetGlobalOptions($uid, $lid, $mindate);
    return $glbl->get_options();
  }
  public function get_cvpath_paging($argobj){
    $argobj['ctype'] = 'pathmax';
    $max_rank = intval($this->ess->get_vars_ess_fromparams($this->params->get_params($argobj)));
    return array(
      'paging' => array(
        'range' => 10,
        'status' => array(
          'pageNum' => 1,
          'from'    => 1,
          'to'      => 10
        ),
        'min' => 1,
        'max' => $max_rank
      )
    );
  }
}


class GetGlobalOptions{
  private $uid,$lid,$lang,$ess;
  function __construct($uid, $lid, $mindate){
    $this->uid   = $uid;
    $this->lid   = $lid;
    $this->mindate = $mindate;
    $this->utils = new Utils();
    $this->ess   = new Ess();
    $this->lang  = $this->utils->get_browser_language();
    $this->wordsdict = $this->utils->wordsdict();
  }
  public function get_options(){
    return array(
      'options'  => $this->get_periods(),
      'contents' => $this->get_contents()
    );
  }
  private function get_periods(){
    $lang = $this->lang;
    $prd = new getPeriods($this->uid, $this->lid, $this->mindate, $this->lang);
    return array(
        'cachelist'     => $prd->cachelist,
        'iscachedexist' => $prd->iscachedexist,
        'currimported'  => $prd->currentselect,
        'lang'          => $lang
    );
  }
  private function get_contents(){
    $uid  = $this->uid;
    $lid  = $this->lid;
    $lang = $this->lang;
    #  content object...
    $title_arr = $this->get_title($lang);
    $summ = new getSummaryOptions($lang);
    $ptcv = new getCvPath($uid, $lid, $lang);
    $contents = array(
      "data"  => array(
        array(
          "type" => "cj_paths",
          "styleClass" => "pathCJPaths",
          "title" => $title_arr['cj_paths']['title'],
          "short_title" => $title_arr['cj_paths']['short_title'],
          "content_name" => "path_cj_paths",
          "isFiltered" => true,
          "contents" => $ptcv->get_contents()
        ),
        array(
          "type" => "global_summary",
          "styleClass" => "pathSummary",
          "title" => $title_arr['global_summary']['title'],
          "short_title" => $title_arr['global_summary']['short_title'],
          "content_name" => "path_global_summary",
          "isFiltered" => false,
          'contents' => $summ->get_contents()
        )
      )
    );
    return $contents;
  }
  private function get_title($lang){
    $title_arr = array(
      'global_summary' => array(
        'title'       => @$this->wordsdict['content-titles']['widget-summ'],
        'short_title' => @$this->wordsdict['content-titles']['widget-summ-short']
      ),
      'cj_paths' => array(
        'title'       => @$this->wordsdict['content-titles']['widget-path'],
        'short_title' => @$this->wordsdict['content-titles']['widget-path-short']
      ),
    );
    return $title_arr;
  }
}


class getPeriods{
  public $bdays,$sampling,$iscachedexist,$currentselect,$cachelist,$cacheidx;
  private $sdate,$edate,$mindate,$maxdate,$ess;
  function __construct($uid, $lid, $mindate, $lang){
    $this->utils = new Utils();
    $this->ess   = new Ess();
    $this->db    = new MysqlCacheList();
    $this->uid  = $uid;
    $this->lid  = $lid;
    $this->mindate = $mindate;
    $this->lang = $lang;
    $this->wordsdict = $this->utils->wordsdict();

    $this->set_defaults();
  }
  private function set_defaults(){
    # if there's already cache file for period
    $cachelist = $this->return_cache_list();
    # local cache
    $sdate=''; $edate=''; $bdays=''; $sampling=''; $memo=''; $cached='';
    $sdate    = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-basic-periof-from', $sdate, '');
    $edate    = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-basic-periof-to',   $edate, '');
    $bdays    = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-basic-prevdays',    $bdays, '');
    $sampling = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-basic-sampling',    $sampling, '');
    $memo     = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-basic-memo',    $memo, '');
    $cached   = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-basic-cached',    $cached, '');
    $cacheds  = explode('/', $cached);
    $cached   = $cacheds[count($cacheds)-1];
    if($memo == null) $memo = '';
    $this->currentselect = ($cached=='') ? array() : array(
      'fname'      => $cached,
      'start_date' => $sdate,
      'end_date'   => $edate,
      'back_days'  => $bdays,
      'sampling'   => $sampling,
      'memo'       => $memo
    );
    $newcachelist = array();
    foreach($cachelist as $idx => $c){
      $c['is_imported'] = (@$c['fname']==$cached);
      if(@$this->currentselect['fname'] == @$c['fname']) $this->currentselect['label'] = @$c['label'];
      array_push($newcachelist, $c);
    }
    $this->cachelist = $newcachelist;
    $this->iscachedexist = count($cachelist) > 0;
    #$this->cacheidx = $cacheidx;
  }
  public function return_cache_list(){
    $uid = $this->uid; 
    $lid = $this->lid; 
    $lang = $this->lang; 
    $words = array(
      'days'  => $this->wordsdict['dateformat-pd'],
      '-'     => $this->wordsdict['-'],
      'year'  => $this->wordsdict['dateformat-yy'],
      'month' => $this->wordsdict['dateformat-mm'],
      'date'  => $this->wordsdict['dateformat-dd']
    );
    $lists  = $this->get_data_s3cache_list();
    $result = array();
    $keys_label = array();
    foreach($lists as $idx => $cacheobj){
      array_push($result, array(
        'fname'      => @$cacheobj['cachedirname'],
        'label'      => @$cacheobj['label'],
        'start_date' => @$cacheobj['start_date'],
        'end_date'   => @$cacheobj['end_date'],
        'back_days'  => @$cacheobj['prev_days'],
        'sampling'   => @$cacheobj['sample'],
        'memo'       => @$cacheobj['memo'],
        'type'       => @$cacheobj['type'],
        'description' => @$cacheobj['memo']
      ));
      array_push($keys_label, @$cacheobj['label']);
    }
    array_multisort($keys_label, SORT_DESC, $result);
    return $result;
  }
  private function get_data_s3cache_list(){
    return $this->db->get_cache_list_for_options($this->uid, $this->lid);
  }
}

class getSummaryOptions{
  function __construct($lang){
    $this->lang  = $lang;
    $this->utils = new Utils();
    $this->wordsdict = $this->utils->wordsdict();
  }
  public function get_contents(){
    $lang = $this->lang;
    $contents = array(
        array(
          'contentType' => 'summ',
          'templateType' => 'summ',
          'title' => @$this->wordsdict['content-titles']['summ']
        ),
        array(
          'contentType'  => 'logCountTrend',
          'templateType' => 'chart',
          'chartType'    => 'linePlusBarChart',
          'title' => @$this->wordsdict['content-titles']['logCountTrend'],
          'options' => array(
            array(
              'name' => 'bintype',
              'type' => 'select',
              'defaultindex' => 2,
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['byminute'],
                  'value'=> '%F %H %M'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['dailyhourly'],
                  'value'=> '%F %H'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['daily'],
                  'value'=> '%F'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['weekly'],
                  'value'=> ($lang == 'Japanese') ? '%W' : '%U'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['monthly'],
                  'value'=> '%m'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['hourly'],
                  'value'=> '%H'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['byday'],
                  'value'=> ($lang == 'Japanese') ? '%u' : '%w'
                )
              )
            ),
            array(
              'name' => 'showPreviousPeriod',
              'type' => 'select',
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['content-options']['yAxis']['periodonly'],
                  'value'=> '0'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['yAxis']['includeprev'],
                  'value'=> '1'
                )
              )
            )
          )
        ),
        array(
          'contentType' => 'logCountTrendMultiUser',
          'templateType' => 'chart',
          'chartType'    => 'lineChart',
          'title' => $this->wordsdict['content-titles']['logCountTrendMultiUser'],
          'options' => array(
            array(
              'name' => 'bintype',
              'type' => 'select',
              'defaultindex' => 2,
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['byminute'],
                  'value'=> '%F %H %M'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['dailyhourly'],
                  'value'=> '%F %H'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['daily'],
                  'value'=> '%F'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['weekly'],
                  'value'=> ($lang == 'Japanese') ? '%W' : '%U'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['monthly'],
                  'value'=> '%m'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['hourly'],
                  'value'=> '%H'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['byday'],
                  'value'=> ($lang == 'Japanese') ? '%u' : '%w'
                )
              )
            ),
            array(
              'name' => 'yAxisType',
              'type' => 'select',
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['ev'],
                  'value' => 'pv'
                ),
                array(
                  'label'=> @$this->wordsdict['ss'],
                  'value' => 'ss'
                ),
                array(
                  'label'=> @$this->wordsdict['uu'],
                  'disabled' => array(
                    'target' => 'bintype',
                      'list'   => array('%F %H %M', '%F %H')
                  ),
                  'value' => 'uu'
                )
              )
            ),
            array(
              'name' => 'showPreviousPeriod',
              'type' => 'select',
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['content-options']['yAxis']['periodonly'],
                  'value'=> '0'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['yAxis']['includeprev'],
                  'value'=> '1'
                )
              )
            )
          )
        ),
        array(
          'contentType' => 'distFreq',
          'templateType' => 'chart',
          'chartType'    => 'lineChart',
          'title' => @$this->wordsdict['content-titles']['distFreq'],
          'options' => array(
            array(
              'name' => 'xAxisType',
              'type' => 'select',
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['content-options']['xAxis']['bin-days'],
                  'value'=> 'days'
                ),
                array(
                  'label'=> @$this->wordsdict['ev'],
                  'value'=> 'depth'
                ),
                array(
                  'label'=> @$this->wordsdict['imp'],
                  'value'=> 'imp'
                ),
                array(
                  'label'=> @$this->wordsdict['click'],
                  'value'=> 'click'
                )
              )
            ),
            array(
              'name' => 'yAxisType',
              'type' => 'select',
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['content-options']['yAxis']['absvalue'],
                  'value'=> 'abs'
                ),
                array(
                  'label'=> @$this->wordsdict['content-options']['yAxis']['cumuvalue'],
                  'value'=> 'cumu'
                )
              )
            ),
            array(
              'name'    => 'ddepth',
              'label'=> @$this->wordsdict['content-options']['viewmax'],
              'min'     => 0,
              'max'     => 1000,
              'span'    => 1,
              'type'    => 'number',
              'default' => 50
            )
          )
        ),
        array(
          'contentType' => 'distFreqPV',
          'templateType' => 'chart',
          'chartType'    => 'linePlusBarChart',
          'title' => @$this->wordsdict['content-titles']['distFreqPV'],
            'options' => array(
              array(
                'name' => 'xAxisType',
                'type' => 'select',
                'list' => array(
                  array(
                    'label'=> 'event',
                    'value'=> 'pv'
                  )
                )
              ),
              array(
                'name' => 'yAxisType',
                'type' => 'select',
                'list' => array(
                  array(
                    'label'=> @$this->wordsdict['content-options']['yAxis']['absvalue'],
                    'value'=> 'abs'
                  ),
                  array(
                    'label'=> @$this->wordsdict['content-options']['yAxis']['cumuvalue'],
                    'value'=> 'cumu'
                  )
                )
              ),
              array(
                'name'    => 'ddepth',
                'label'=> @$this->wordsdict['content-options']['viewmax'],
                'min'     => 0,
                'max'     => 1000,
                'span'    => 1,
                'type'    => 'number',
                'default' => 50
              )
            )
        ),
        array(
          'contentType'  => 'pieBrowsers',
          'templateType' => 'chart',
          'chartType'    => 'pieChart',
          'title' => @$this->wordsdict['content-titles']['pieBrowsers'],
            'options' => array(
          )
        ),
        array(
          'contentType' => 'beforeAfterTable',
          'templateType' => 'table',
          'title' => @$this->wordsdict['content-titles']['beforeAfterTable'],
          'options' => array(
            array(
              'name' => 'logtype',
              'type' => 'select',
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['weblog'],
                  'value'  => 'filterbysite',
                  'children' => array(
                    'name' => 'colms',
                    'type' => 'select',
                    'list' => array(
                      array(
                        'label'=> @$this->wordsdict['domain'],
                        'value'  => array('domain','','',''),
                      ),
                      array(
                        'label'=> @$this->wordsdict['page'],
                        'value'  => array('','page','',''),
                      ),
                      array(
                        'label'=> @$this->wordsdict['pagename'],
                        'value'  => array('','','pname',''),
                      ),
                      array(
                        'label'=> @$this->wordsdict['ref'],
                        'value'  => array('','','','ref'),
                      ),
                      array(
                        'label'=> @$this->wordsdict['all'],
                        'title'  => array(@$this->wordsdict['domain'], @$this->wordsdict['page'], @$this->wordsdict['pagename'], @$this->wordsdict['ref']),
                        'value'  => array('domain','page','pname','ref'),
                      )
                    )
                 )
               ),
               array(
                 'label'=> @$this->wordsdict['advlog'],
                 'value'  => 'filterbyad',
                 'children' => array(
                   'name' => 'colms',
                   'type' => 'select',
                   'list' => array(
                     array(
                       'label'=> @$this->wordsdict['camp'],
                       'value'  => array('campaign','','',''),
                     ),
                     array(
                       'label'=> @$this->wordsdict['media'],
                       'value'  => array('','media','',''),
                     ),
                     array(
                       'label'=> @$this->wordsdict['adname'],
                       'value'  => array('','','pname',''),
                     ),
                     array(
                       'label'=> @$this->wordsdict['placement'],
                       'value'  => array('','','','ref'),
                     ),
                     array(
                       'label'=> @$this->wordsdict['all'],
                       'title'  => array(@$this->wordsdict['camp'],@$this->wordsdict['media'],@$this->wordsdict['adname'],@$this->wordsdict['placement']),
                       'value'  => array('campaign','media','pname','ref'),
                     )
                   )
                 )
               )
             )
            )
          )
        ),
        array(
          'contentType' => 'detailTableEventCount',
          'templateType' => 'table',
          'title' => @$this->wordsdict['content-titles']['detailTableEventCount'],
          'options' => array(
            array(
              'name' => 'logtype',
              'type' => 'select',
              'defaultindex' => 0,
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['weblog'],
                  'value'  => 'filterbysite',
                  'children' => array(
                    'name' => 'colms',
                     'type' => 'select',
                     'list' => array(
                       array(
                         'label'=> @$this->wordsdict['device'],
                         'value'  => array('s:bwos')
                       ),
                       array(
                         'label'=> @$this->wordsdict['domainpage'],
                         'value'  => array('s:domain', 's:page')
                       ),
                       array(
                         'label'=> @$this->wordsdict['ip'],
                         'value'  => array('ip:ip'),
                       ),
                       array(
                         'label'=> @$this->wordsdict['skey'],
                         'value'  => array('s:skey'),
                       )
                     )
                  )
                ),
                array(
                  'label'=> @$this->wordsdict['advlog'],
                  'value'  => 'filterbyad',
                  'children' => array(
                    'name' => 'colms',
                    'type' => 'select',
                    'list' => array(
                      array(
                        'label'=> @$this->wordsdict['device'],
                         'value'  => array('s:bwos'),
                      ),
                      array(
                        'label'=> @$this->wordsdict['campaignmedia'],
                        'title'  => array(@$this->wordsdict['campaign'], @$this->wordsdict['media']),
                        'value'  => array('s:domain','s:page'),
                      ),
                      array(
                        'label'=> @$this->wordsdict['ip'],
                         'value'  => array('ip:ip'),
                      )
                    )
                  )
                )
              )
            )
          )
        ),
        array(
          'contentType' => 'detailTableEntry',
          'templateType' => 'table',
          'title' => @$this->wordsdict['content-titles']['detailTableEntry'],
          'options' => array(
            array(
              'name' => 'colmswtfilter',
              'type' => 'select',
              'defaultindex' => 0,
              'list' => array(
                array(
                  'label'=> @$this->wordsdict['enttype'],
                  'value'  => array(
                    'colms'  => array('i:ent'),
                    'filter' => array(
                      'label' => 'pnum',
                      'value' => 1
                    ),
                    'logtype' => 'filterbysite'
                  )
                ),
                array(
                  'label'=> @$this->wordsdict['refdomain'],
                  'value'  => array(
                    'colms'  => array('s:ref'),
                    'filter' => array(
                      'label' => 'ent',
                      'value' => '"referral"'
                    ),
                    'logtype' => 'filterbysite'
                  )
                ),
                array(
                  'label'=> @$this->wordsdict['organic'],
                  'title'  => array(@$this->wordsdict['sengine'], @$this->wordsdict['skey'], @$this->wordsdict['entrypage']),
                  'value'  => array(
                    'colms'  => array('s:ref', 's:skey', 's:page'),
                    'filter' => array(
                      'label' => 'ent',
                      'value' => '"organic"'
                    ),
                    'logtype' => 'filterbysite'
                  )
                ),
                array(
                  'label'  => @$this->wordsdict['listing'],
                  'title'  => array(@$this->wordsdict['ref'], @$this->wordsdict['skey'], @$this->wordsdict['entrypage']),
                  'value'  => array(
                    'colms'  => array('s:ref', 's:skey', 's:page'),
                    'filter' => array(
                      'label' => 'ent',
                      'value' => '"listing"'
                    ),
                    'logtype' => 'filterbysite'
                  )
                ),
                array(
                  'label'  => @$this->wordsdict['banner'],
                  'title'  => array(@$this->wordsdict['placement'], @$this->wordsdict['entrypage']),
                  'value'  => array(
                    'colms'  => array('s:ref', 's:page'),
                    'filter' => array(
                      'label' => 'ent',
                      'value' => '"click"'
                    ),
                    'logtype' => 'filterbysite'
                  )
                )#,
                #array(
                #  'label'  => @$this->wordsdict['directentry'],
                #  'title'  => array(@$this->wordsdict['domain'], @$this->wordsdict['page']),
                #  'value'  => array(
                #    'colms'  => array('s:domain', 's:page'),
                #    'filter' => array(
                #      'label' => 'ent',
                #      'value' => 15 
                #    ),
                #    'logtype' => 'filterbysite'
                #  )
                #)
              )
            )
          )
        ),
        array(
          'contentType'  => 'detailTableRegionMap',
          'templateType' => 'table',
          'title'        => @$this->wordsdict['content-titles']['detailTableRegionMap'],
        ),
        array(
          'contentType' => 'attrScore',
          'templateType' => 'table',
          'title' => @$this->wordsdict['content-titles']['attrScore'],
          'options' => array(
            array(
              'name' => 'attrmodel',
              'type' => 'select',
              'defaultindex' => 0,
              'list' => array(
                array(
                  'label'  => @$this->wordsdict['attr-models']['lastclick'],
                  'value'  => 'last_click'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['lastclick2'],
                  'value'  => 'last_click_2'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['clickonly'],
                  'value'  => 'click_only'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['clickonly2'],
                  'value'  => 'click_only_2'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['clickonlyseo'],
                  'value'  => 'click_only_seo'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['clickonlyseo2'],
                  'value'  => 'click_only_seo_2'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['ctrwgt'],
                  'value'  => 'ctr_wgt'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['ctrwgtseo'],
                  'value'  => 'ctr_wgt_seo'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['firstclick'],
                  'value'  => 'first_click'
                ),
                array(
                  'label'  => @$this->wordsdict['attr-models']['firstclick2'],
                  'value'  => 'first_click_2'
                ),
               )
             ),
             array(
               'name' => 'colms',
               'type' => 'select',
               'defaultindex' => 0,
               'list' => array(
                 array(
                   'label'  => @$this->wordsdict['camp'],
                   'value'  => array('campaign','','','')
                 ),
                 array(
                   'label'  => @$this->wordsdict['site'],
                   'value'  => array('','site','','')
                 ),
                 array(
                   'label'  => @$this->wordsdict['adname'],
                   'value'  => array('','','adkeyword','')
                 ),
                 array(
                   'label'  => @$this->wordsdict['placement'],
                   'value'  => array('','','','placement')
                 ),
                 array(
                   'label'  => @$this->wordsdict['all'],
                   'title'  => array(@$this->wordsdict['camp'], @$this->wordsdict['site'], @$this->wordsdict['adname'], @$this->wordsdict['placement']),
                   'value'  => array('campaign','site','adkeyword','placement')
                 )
               )
             ),
             array(
               'name' => 'intrt',
               'type' => 'select',
               'defaultindex' => 2,
               'list' => array(
                 array(
                   'label'  => @$this->wordsdict['banner'],
                   'value'  => array(1,2),
                ),
                array(
                  'label'  => @$this->wordsdict['listing'],
                  'value'  => array(3),
                ),
                array(
                  'label'  => @$this->wordsdict['noquery'],
                  'value'  => array()
                )
              )
            )
          )
        ),
        array(
          'contentType' => 'attrScoreFMD',
          'templateType' => 'table',
          'title' => @$this->wordsdict['content-titles']['attrScoreFMD'],
          'options' => array(
            array(
              'name' => 'attrmodel',
              'type' => 'select',
              'defaultindex' => 0,
              'list' => array(
                array(
                  'label'  => '',
                  'value'  => 'first_mid_last'
                )
              )
            ),
            array(
               'name' => 'colms',
               'type' => 'select',
               'defaultindex' => 0,
               'list' => array(
                 array(
                   'label'  => @$this->wordsdict['camp'],
                   'value'  => array('campaign','','','')
                 ),
                 array(
                   'label'  => @$this->wordsdict['site'],
                   'value'  => array('','site','','')
                 ),
                 array(
                   'label'  => @$this->wordsdict['adname'],
                   'value'  => array('','','adkeyword','')
                 ),
                 array(
                   'label'  => @$this->wordsdict['placement'],
                   'value'  => array('','','','placement')
                 ),
                 array(
                   'label'  => @$this->wordsdict['all'],
                   'title'  => array( @$this->wordsdict['camp'],  @$this->wordsdict['site'],  @$this->wordsdict['adname'],  @$this->wordsdict['placement']),
                   'value'  => array('campaign','site','adkeyword','placement')
                 )
               )
            ),
            array(
               'name' => 'intrt',
               'type' => 'select',
               'defaultindex' => 0,
               'list' => array(
                array(
                  'label'  =>  @$this->wordsdict['noquery'],
                  'value'  => array()
                )
              )
            )
          )
        )
      );
    return $contents;
  }
}

class getCvPath{
  private $uid,$lid,$lang,$utils,$ess;
  function __construct($uid, $lid, $lang){
    $this->uid   = $uid;
    $this->lid   = $lid;
    $this->lang  = $lang;
    $this->utils = new Utils();
    $this->ess   = new Ess();
    $this->wordsdict = $this->utils->wordsdict();
  }
  public function get_contents(){
    $lang = $this->lang;
    #    Path Order Flag
    $pathord        = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-path-order', 0, '');
    $tillcvflg      = $this->ess->get_cached_vars($this->uid, $this->lid, 'udbd-path-tillcv', 1, '');
  
    $result = array(
#array(
      'pathorderflg'   => array(
        'value' => $pathord,
         'options' => array(
                        array('value'=>0, 'label'=> @$this->wordsdict['forward']), 
                        array('value'=>1, 'label'=> @$this->wordsdict['backward'])
                      )
       ),
      'tillcvflg'   => array(
        'value' => $tillcvflg,
        'options' => array(
                       array('value'=>1, 'label'=> @$this->wordsdict['tillcv']), 
                       array('value'=>0, 'label'=> @$this->wordsdict['tillend'])
                     )
      ),
      'freqdetail'   => array(
        'value' => 'depth',
        'options' => array(
                       array('value'=>'depth', 'label'=> @$this->wordsdict['ev']),
                       array('value'=>'days',  'label'=> @$this->wordsdict['content-options']['xAxis']['bin-days']), 
                       array('value'=>'imp', 'label'=> @$this->wordsdict['imp']),
                       array('value'=>'click', 'label'=> @$this->wordsdict['click'])
                    )
      ),
      'pathdepth'   => array(
        'value'   => 5,
        'options' => array(1,2,3,4,5,10,20)
      ),
      'searchbykeywords'   => array(
        'value'   => '',
        'defidx'  => 0,
        'options' => array(
          array(
            'value'  => 'any',
            'label'  => @$this->wordsdict['toui-contents']['opt-cvpath']['any']['label'],
            'defstr' => @$this->wordsdict['toui-contents']['opt-cvpath']['any']['description'],
          ),
          array(
            'value'  => '1st',
            'label'  => @$this->wordsdict['toui-contents']['opt-cvpath']['1st']['label'],
            'defstr' => @$this->wordsdict['toui-contents']['opt-cvpath']['1st']['description'],
          ),
          array(
            'value'  => 'last',
            'label'  => @$this->wordsdict['toui-contents']['opt-cvpath']['last']['label'],
            'defstr' => @$this->wordsdict['toui-contents']['opt-cvpath']['last']['description'],
          )
        )
      )
#)
    );
    return $result;
    
  }
}


?>
