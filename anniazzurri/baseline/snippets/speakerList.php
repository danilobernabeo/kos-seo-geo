<?php
$thelist = array();
if ($handle = opendir($input)) {
    while (false !== ($file = readdir($handle))) {
      if ($file != "." && $file != "..") {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $thelist[] = '<tr><th scope="row" class="py-3"><i class="ki ki-terapie-scarica-file text-azure"></i></th><td class="py-3">'.$name.'</td><td class="text-end py-3"><a class="d-flex justify-content-center align-items-center" href="'.$input.'/'.$file.'" target="_blank" rel="noopener noreferrer"><i class="bi bi-arrow-down-circle"></i><span class="ps-2 d-none d-lg-inline-block font-weight-600 fs-6">Scarica Documento</span></a></td></tr>';
      }
    }
    closedir($handle);
  }
  sort($thelist);
foreach ($thelist as $file) {
  echo $file;
}