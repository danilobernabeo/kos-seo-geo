<?php
$currentPageId = $modx->resource->get('id');

return $modx->runSnippet('pdoResources', array(
   'parents' => 280,
   'tpl' => '@INLINE
       <div class="col-12 col-md-6 equipe-persona">
           <a href="[[~[[+id]]]]" title="[[+pagetitle]]">
               <img class="equipe-foto" src="[[+tv.infoImage:default=`/assets/img/medico-thumb.svg`]]" alt="[[+pagetitle]]">
           </a>
           <div class="equipe-info">
               <span class="equipe-ruolo">
                   <a href="[[~[[+id]]]]" title="[[+pagetitle]]">[[+pagetitle]]</a>
               </span>
               <span class="equipe-nome">[[+tv.ruolo]]</span>
           </div>                                       
       </div>
   ',
   'tvPrefix' => 'tv.',
   'includeTVs' => 'infoImage,ruolo,idSedeLavoro',
   'tvFilters' => 'idSedeLavoro==' . $currentPageId,
   'sortby' => 'pagetitle',
   'sortdir' => 'ASC',
   'limit' => 0
));