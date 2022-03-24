<?php

namespace TroopTracker\ForumCatch;

//use XF\Entity\Post;

class Post extends XFCP_Post
{
    public static function getStructure(\XF\Mvc\Entity\Structure &$structure)
    {
        $structure = parent::getStructure($structure);
        //error_log( print_r($structure, TRUE) );
        error_log("test");
        ecvdsfgasdf
        return $structure;
    }
}