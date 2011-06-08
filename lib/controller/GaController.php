<?
class GaController extends WaxController{

  public $api = false;
  public $config = array();
  public $summary_config = array();
  public $compare = array();
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
      //grab the setups
      $this->compare = $this->summary_config['compare'];
      //go over each compare and setup the data
      foreach($this->compare as $k=>$comp){
        $comp = $this->compare[$k] = $this->times($comp);
        //get the data and process it for each
        foreach($this->summary_config['summaries'] as $nm=>$sum){
          $func = $sum['processfunction'];
          $col = $sum['columnname'];
          $this->results[$col][$k] = $this->$func($this->data($sum, $comp, $this->results));
        }        
      }
    }
  }
  
  protected function process_visits($raw){    
    return $this->flat_results($raw, 'ga:visits');
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

  protected function times($comp){
    $sm = date("m")+$comp['month'];
    $sd = 1;
    $sy = date("Y")+$comp['year'];
    $comp['times']['start']['ts'] = mktime(0,0,0, $sm, $sd, $sy);
    $comp['times']['start']['ymd'] = date("Y-m-d", $comp['times']['start']['ts']);
    $comp['times']['start']['eng'] = date("F Y", $comp['times']['start']['ts']);
    
    $em = $sm+$comp['interval'];
    $ed = 0;
    $ey = date("Y")+$comp['year'];
    $comp['times']['end']['ts'] = mktime(0,0,0, $em, $ed, $ey);
    $comp['times']['end']['ymd'] = date("Y-m-d", $comp['times']['end']['ts']);
    $comp['times']['end']['eng'] = date("F Y", $comp['times']['end']['ts']);
    
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