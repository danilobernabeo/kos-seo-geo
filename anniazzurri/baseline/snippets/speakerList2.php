<?php
$thelist = array();
if ($handle = opendir($input)) {
    while (false !== ($file = readdir($handle))) {
      if ($file != "." && $file != "..") {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $thelist[] = '<div card-news="" class="col-12 col-lg-4"><div class="card border-0 shadow"><img src='.$input.'/'.$file.' class="card-img-top"><div class="p-2"><audio class="px-1" style="width: 100%;" controls="controls"> <source src="assets/audio/speaker/'.$name.'.mp3" type="audio/mp3">Il tuo browser non supporta elementi audio.</audio></div><div class="p-2 text-center">&nbsp;</div></div></div>';
      }
    }
    closedir($handle);
  }
  sort($thelist);
foreach ($thelist as $file) {
  echo $file;
}