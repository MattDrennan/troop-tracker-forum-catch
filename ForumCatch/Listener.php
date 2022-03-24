<?php

/**
 * This file listens posts to Xenforo.
 *
 * @author  Matthew Drennan
 *
 */

namespace TroopTracker\ForumCatch;

use XF\Mvc\Entity\Entity;

// Set absolute path to cred file here
require "/Applications/MAMP/htdocs/501-troop-tracker/cred.php";

class Listener
{
    public static function TroopTracker_Entity_Save($entity)
    {
        // Set forums to check
        $forumCheck = array(445, 7, 186, 8, 73, 9);

        // Set event node check
        $isEventNode = false;

        // Check if posting in event section
        foreach($forumCheck as $node)
        {
            if($node == $entity->Thread->node_id)
            {
                $isEventNode = true;
            }
        }

        // If not event node, stop script
        if(!$isEventNode) { return false; }

        // Don't do below if we do not have variables
        if(!isset($entity->thread_id)) { return false; }

        // Connect to server
        $conn = new \mysqli(dbServer, dbUser, dbPassword, dbName);

        // Count number of posts
        $posts = $conn->query("SELECT comments.id, events.post_id FROM comments, events WHERE comments.post_id = '".$entity->post_id."' OR events.post_id = '".$entity->post_id."'");

        // Check if there are events connected to Xenforo
        $eventCheck = $conn->query("SELECT id FROM events WHERE thread_id = '".$entity->thread_id."'");

        // If no events connected, stop script
        if($eventCheck->num_rows == 0) { return false; }

        // No posts exist
        if($posts->num_rows == 0)
        {
            // Insert
            xenforoInsertComment(@getEventByThreadID($entity->thread_id), @getIDFromUserID($entity->user_id), $entity->post_id, $entity->message);
        }
        else
        {
            // Update
            $conn->query("UPDATE comments SET comment = '".$entity->message."' WHERE post_id = '".$entity->post_id."'");
        }
    }

    public static function TroopTracker_Entity_Delete($entity)
    {
        // Don't do below if we do not have variables
        if(!isset($entity->thread_id)) { return false; }

        // Connect to server
        $conn = new \mysqli(dbServer, dbUser, dbPassword, dbName);

        // Delete
        $conn->query("DELETE FROM comments WHERE post_id = '".$entity->post_id."'");
    }
}

/**
 * Inserts reply to Troop Tracker database and notification system
 * 
 * @param $troopid int The troop ID of the event
 * @param $user_id int the user_id of the trooper
 * @param $message string The body of the reply message
 * @return void
*/
function xenforoInsertComment($troopid, $user_id, $post_id, $message)
{
    // Connect to server
    $conn = new \mysqli(dbServer, dbUser, dbPassword, dbName);

    // Insert into Troop Tracker comment table
    $conn->query("INSERT INTO comments (troopid, trooperid, comment, important, post_id) VALUES ('".$troopid."', '".$user_id."', '".$message."', 0, '".$post_id."')") or die($conn->error);

    // Get last ID of comment from Troop Tracker database
    $last_id = $conn->insert_id;

    // Insert into Troop Tracker notification table
    $conn->query("INSERT INTO notification_check (troopid, commentid) VALUES ('".$troopid."', '".$last_id."')");
}

/**
 * Returns the event ID on the Troop Tracker using the Xenforo ID
 * 
 * @param int $thread_id The ID of the thread
 * @return int Returns the troop ID
*/
function getEventByThreadID($thread_id)
{
    // Connect to server
    $conn = new \mysqli(dbServer, dbUser, dbPassword, dbName);
    
    $query = "SELECT * FROM events WHERE thread_id = '".$thread_id."'";

    if ($result = mysqli_query($conn, $query))
    {
        while ($db = mysqli_fetch_object($result))
        {
            return $db->id;
        }
    }
}

/**
 * Get's the Troop Tracker ID using Xenforo ID
 * 
 * @param int $id The Xenforo ID of the trooper
 * @return boolean Returns the Troop Tracker ID
*/
function getIDFromUserID($id)
{
    // Connect to server
    $conn = new \mysqli(dbServer, dbUser, dbPassword, dbName);
    
    // Set up value
    $value = 0;
    
    // Get data
    $query = "SELECT id FROM troopers WHERE user_id = '".$id."'";
    
    // Run query...
    if ($result = mysqli_query($conn, $query))
    {
        while ($db = mysqli_fetch_object($result))
        {
            // Set
            $value = $db->id;
        }
    }
    
    // Return
    return $value;
}