<?php

$md = new MediawikiDump(true);
$md->imgGetFiles(false);

print "\n---PAGES---\n";
$md->paglstGetXml(true,['outpag'=>'01','apprefix'=>'Atividade']);
$md->paglstGetXml(true,['outpag'=>'02','apprefix'=>'Serviço']);
$md->paglstGetXml(true,['outpag'=>'03','apprefix'=>'Wiki']);
$md->paglstGetXml(true,['outpag'=>'04','apprefix'=>'Contrato']);
//Página_principal

// or other as https://www.mediawiki.org/wiki/Manual:Namespace
$md->paglstGetXml(true,['outpag'=>'05','apnamespace'=>'10']); // templates
$md->paglstGetXml(true,['outpag'=>'06','apnamespace'=>'11']);
$md->paglstGetXml(true,['outpag'=>'07','apnamespace'=>'14']); // categories
$md->paglstGetXml(true,['outpag'=>'08','apnamespace'=>'15']);
$md->paglstGetXml(true,['outpag'=>'09','apnamespace'=>'3000']);
$md->paglstGetXml(true,['outpag'=>'10','apnamespace'=>'3001']);
$md->paglstGetXml(true,['outpag'=>'11','apnamespace'=>'2']); // user
$md->paglstGetXml(true,['outpag'=>'12','apnamespace'=>'3']);

print "\n  ---\n";

//$md->pagGetXml();
$md->paglstGetLst(); // ver array com nomes (title usa prefixo do ns)
// falta colerar o export XML desejado
// ignorar users estranhos 
// rodar post de requisição do dump completo de N páginas

print "\n";

//////////////
class MediawikiDump {

  public $url_wiki  = 'http://www.xmlfusion.org/wiki-do-mei';
  public $dump_path = __DIR__.'/../dump';  // 'dump/imgs', etc.
  public $imgsXml   = NULL;
  public $paglst    = NULL;

  function __construct($refresh=false) {
    $this->dump_path = realpath($this->dump_path);
    $this->dump_path_label = str_replace(__DIR__,'',$this->dump_path);
		$this->dump_imgsPath	= "{$this->dump_path}/imgs";
    $this->dump_imgsPath_label = str_replace(__DIR__,'',$this->dump_imgsPath);
    $this->dump_imgsPath_ns	= 6; // default namespace
    $this->dump_imgsFile	  = "{$this->dump_path}/imgs.xml";
    $this->dump_pagsFile	  = "{$this->dump_path}/pags_list"; // .xml
    $this->imgGetXml($refresh);
	}

  function imgGetXml($refresh=false) {
    $url_ximg = '/api.php?action=query&list=allimages&ailimit=500&format=xml';
    if ($refresh || !file_exists($this->dump_imgsFile)) {
      $this->imgsXml = file_get_contents($this->url_wiki.$url_ximg);
      file_put_contents($this->dump_imgsFile,$this->imgsXml);
    } else
      $this->imgsXml = file_get_contents($this->dump_imgsFile);
    return $this;
  }

  function imgGetFiles($refresh=false) {
    $sdom = simplexml_load_string($this->imgsXml);
    foreach($sdom->query->allimages->img as $d) {
      if ($d['ns']!=$this->dump_imgsPath_ns) die("\nERRO - namespace estranho ($d[ns]) na imagem '$d[name]'\n");
      // using only attribs name, timestamp and url
      $f = "{$this->dump_imgsPath}/$d[name]";
      $flabel = $d['name'];
      if ($refresh || !file_exists($f)) {
        $this->cpUrlToFile($d['url'],$f);
        print "\n--ok wget $flabel to {$this->dump_imgsPath_label}";
      } else {
        $mtime = filemtime($f); // FALSE on failure
        $mtime_orig = strtotime($d['timestamp']);
        if ($mtime>$mtime_orig)
          print "\n-- ok $flabel in the dump folder {$this->dump_imgsPath_label}";
        else {
          print "\n-- !OPS $flabel com $mtime MENOR que $mtime_orig: recarregando";
          $this->cpUrlToFile($d['url'],$f);
          print "...\n\t--ok wget $flabel to {$this->dump_imgsPath_label}";
        }
      } // if exists
    } // for
  } // func


  private function cpUrlToFile($url,$f) {
    if(!@copy($url,$f)) {
      $errors= error_get_last();
      die ("\nCOPY ERROR at '$url  to $f':\n{$errors['type']}\n{$errors['message']}\n");
    } else
      return 1;
  }

  function paglstGetXml($refresh=false,$conf=['apnamespace'=>'0']) {
    // namesmape https://www.mediawiki.org/wiki/Manual:Namespace
    $outpag_idx=0;
    if (isset($conf['outpag'])) {
      $outpag_idx=$conf['outpag'];
      unset($conf['outpag']);
    }
    $f = "{$this->dump_pagsFile}$outpag_idx.xml";
    $conf0 = ['action'=>'query', 'list'=>'allpages', 'aplimit'=>500, 'format'=>'xml'];
    $url_ximg = $this->array_urlencode( array_merge($conf0,$conf), '/api.php' );

    print "\n --- URL GET $url_ximg to $f\n";
    //apprefix=Atividade, apfrom=Atividade
    // puchar primeiros os itens com pageid<250 ou 260.
    if ($refresh || !file_exists($f)) {
      $this->pagsXml = file_get_contents($this->url_wiki.$url_ximg);
      file_put_contents($f,$this->pagsXml);
    } else
      $this->pagsXml = file_get_contents($f);
    return $this;
  }

  private function array_urlencode($a,$url='') {
    $aux = [];
    foreach($a as $k=>$v) $aux[]="$k=".urlencode($v);
    $str = join('&',$aux);
    return $url? "$url?$str": $str;
  }

  public function paglstGetLst() {
    $lst = "{$this->dump_pagsFile}*.xml";
    echo "\n list of $lst... ";
    $this->paglst = [];
    foreach (glob($lst) as $f) {
      echo "\n-- from $f:";
      $sdom = simplexml_load_file($f);
      $n=0;
      foreach($sdom->query->allpages->p as $d) {
        $this->paglst[ (string) $d['pageid'] ] = [
          'ns'=> (int) $d['ns'],
          'title'=> (string) $d['title']
        ];
        $n++;
      } // for p
      echo " $n records";
    } //for files
    var_dump($this->paglst);
  }

} // class



// LIB:



 ?>
