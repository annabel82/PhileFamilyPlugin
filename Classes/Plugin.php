<?php
namespace Phile\Plugin\Annabel82\FamilyTwo;

class Plugin extends \Phile\Plugin\AbstractPlugin implements \Phile\Gateway\EventObserverInterface {

private $curr_page_path;


// --------------------------------------------


public function __construct() {

    \Phile\Event::registerEvent('template_engine_registered', $this);
}


// --------------------------------------------


public function on($eventKey, $data = null) {

    if($eventKey == 'template_engine_registered'){

        $this->curr_path = $data['data']['current_page']->getFilePath();
        $this->curr_file = basename($this->curr_path);
        $this->curr_dir  = dirname($this->curr_path);

        $data['data'] = $this->buildFamily($data['data']);

        return $data;
    }
}


// --------------------------------------------


private function buildFamily($data) {

    $ancestor_paths = $this->buildAncestorPaths($data);
    $siblings       = [];
    $ancestors      = [];

    foreach($data['pages'] as $Page) {

        $Page->is_dir  = FALSE;
        $loop_path     = $Page->getFilePath();
        $loop_file     = basename($loop_path);
        $loop_dir      = dirname($loop_path);
        $loop_granddir = dirname($loop_dir);

        if($this->settings['show_current_location']) {                                      // If we have set TRUE for show_current_location in the config.php

            if($loop_dir == $this->curr_dir) {                                              // We've found a sibling page irrespective of it being out current page
             
               $siblings[] = $Page;                                                         // So add it even if it is our current page
            }

        } else {                                                                            // If we have set FALSE for show_current_location in the config.php

            if($loop_dir == $this->curr_dir && ($loop_path != $this->curr_path)) {          // We've found a subling page so long as it's not our current page

               $siblings[] = $Page;                                                         // Add it to sibling list
            }                                                                           
        } 

        if($this->settings['sibling_dirs'] && $this->curr_dir == $loop_granddir && $loop_file == 'index'.CONTENT_EXT) { 
                                                                                            // We've found a sibling directory, so add the index 
            $Page->is_dir = TRUE;                                                           // file from that directory as a sibling
            $siblings[]   = $Page;
        }

        if(in_array($loop_path,$ancestor_paths)) {                                          // We've found an ancestor file

            $ancestors[$Page->getFilePath()] = $Page;
        }
    }

    if($this->settings['ancestor_sort'] && $this->settings['ancestor_sort'] == 'desc') {

        ksort($ancestors);

    } else {
    
        krsort($ancestors);
    }

    $data['siblings']  = $siblings;
    $data['ancestors'] = $ancestors;

    return $data;
}


/*
* This function builds an array of paths that the ancestors of the current page must have.  
* Used by buildFamily to know when an ancestor page has been found when looping through all pages
*
* Returns an array of paths, sorted by first ancestor, to parent
*/

private function buildAncestorPaths($data) {

    $ancestor_paths     = [];
    $index_filename     = 'index'.CONTENT_EXT;
    $content_dir        = rtrim(CONTENT_DIR,'/');
    $content_dir_length = strlen($content_dir);

    $active_dir         = ($this->curr_file == $index_filename)                             // If we're viewing an index file
                                                            ? dirname($this->curr_dir)      // the first ancesteor is in the parent dir
                                                            : $this->curr_dir;              // otherwise the first ancestor is the index file in current dir.

    while(strlen($active_dir) >= $content_dir_length){

        $ancestor_paths[] = $active_dir.'/'.$index_filename;
        $active_dir       = dirname($active_dir);
    }

    return $ancestor_paths;
    }
}


