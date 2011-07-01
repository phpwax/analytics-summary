<?
class GaController extends WaxController{

  public $api = false;
  public $config = array();
  public $summary_config = array();
  public $compare = array();
  public $times = array();
  public $result = array();
  public $use_plugin = "ga";
	public $use_layout = "ga";

  public function controller_global(){
    set_time_limit(0);
  }
  public function index(){
    $this->api = new GA;
    $this->config = $this->analytics_config();
    if(($this->summary_config = $this->config['summary']) && $this->api->login($this->config['email'], $this->config['password']) ){
      if(Request::param('account')){
        //grab the setups
        $ocompare = $this->summary_config['compare'];
        //find accounts
        if(!$accounts = Request::param('account')) $accounts = array_keys($this->compare);
        $this->accounts = $accounts;
        foreach($accounts as $i=>$account){
          $this->compare[$i] = $comp = $this->times($ocompare[$account], $i);
          foreach($this->summary_config['summaries'] as $nm=>$sum){
            $func = $sum['processfunction'];
            $col = $sum['columnname'];
            //this so you can limit the query to a single comparision
            $res = $this->$func($this->data($sum, $comp), $col, $this->results, $comp);
            $this->results[$res['col']][$i] = $res['data'];
          }
        }
        
        
      }
    } 

  }

  protected function process_keywords($raw, $nm, $other_results, $setup){
    $res = array();
    foreach($raw as $i=>$vals) if($i != "(not set)") $res[$i] = array_shift($vals);
    return array('col'=>$nm, 'data'=>$res);
  }

  protected function process_sources($raw, $nm, $other_results, $setup){
    $name = $nm ." ". array_shift(array_keys($raw));
    $val = array_shift(array_shift($raw));
    return array('data'=>$val, 'col'=>$name);
  }

  protected function process_goal($raw, $nm, $other_results, $setup){
    $val = array_shift($raw);
    array_unshift($raw, $val);
    $col = array_shift(array_keys($val));
    return array('data'=>$this->flat_results($raw, $col), 'col'=>$nm);
  }

  protected function process_visits($raw, $nm, $other_results, $setup){
    return array('data'=>$this->flat_results($raw, 'ga:visits'), 'col'=>$nm);
  }

  protected function flat_results($data, $col){
    $res = 0;
    foreach($data as $i=>$v) $res += $v[$col];
    return $res;
  }



  protected function data($setup, $comp){
    return $this->api->data($comp['id'], $setup['dimensions'], $setup['metric'], $setup['sort'],
                            $comp['times']['start']['ymd'], $comp['times']['end']['ymd'], $setup['max_results'],
                            $setup['start_index'], $setup['segment'], $setup['version']);
  }

  protected function times($comp, $i){
    $posted_m = Request::param("month");
    $posted_y = Request::param("year");

    if(!$sm = $posted_m[$i]) $sm = date("m")+$comp['month'];
    $sd = 1;
    if(!$sy = $posted_y[$i]) $sy = date("Y")+$comp['year'];
    $comp['times']['start']['ts'] = mktime(0,0,0, $sm, $sd, $sy);
    $comp['times']['start']['ymd'] = date("Y-m-d", $comp['times']['start']['ts']);
    $comp['times']['start']['eng'] = date("F Y", $comp['times']['start']['ts']);

    $em = $sm+$comp['interval'];
    $ed = 0;
    $ey = $sy;
    $comp['times']['end']['ts'] = mktime(0,0,0, $em, $ed, $ey);
    $comp['times']['end']['ymd'] = date("Y-m-d", $comp['times']['end']['ts']);
    $comp['times']['end']['eng'] = date("F Y", $comp['times']['end']['ts']);

    $this->times[$i]['month'] = $sm;
    $this->times[$i]['year'] = $sy;
    $this->times[$i]['account'] = $comp['id'];
    return $comp;
  }


  /*
   summary:{
    compare:[
      {
        id: XX,
        month:-1,
        year:0,
        interval:1
      },
      {
        id: XX,
        month:-1,
        year:-1,
        interval:1
      }
    ]
    summaries:{
      visits:{
        columname:'visits'
        dimension: X,
        metric: X,
        sort: X,
        max_results: 1,
        start_index: 1,
        segment: ga::-11,
        version: 2,
        processfunction: name
      },
      sources:{
        columname:'sources',
        dimension: X,
        metric: X,
        sort: X,
        max_results: 1,
        start_index: 1,
        segment: ga::-11,
        version: 2,
        processfunction: name
      }
    }
  }
  */
  protected function analytics_config(){
    return Config::get('analytics');
  }
}
?>