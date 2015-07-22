<?php
  // include global files
  include_once "globalFunctions.php";
  include_once "db.php";
  
  // this class is for listing posts on the homepage
  class posts {
  
    // create property for later definition
    public $pages;
    
    // create count function to count the max number of pages with a given pagesize
    public function count($obj){
    
      // define global variable within function scope
      global $dbh;
    
      foreach($obj as $key => $value){
        $$key = $value;
      }
    
      $tagSearch = $dbh->prepare("SELECT id FROM tags WHERE name='$tag'");
      $tagSearch->execute();
      $tagFetch = $tagSearch->fetch(PDO::FETCH_ASSOC);
      $tagId = $tagFetch['id'];
    
      // get number of rows in table
      if(empty($tag)){
        $sth = $dbh->prepare("SELECT id FROM posts WHERE draft='false'");
        $sth->execute();
        $nRows = $sth->rowCount();
      } else {
        $sth = $dbh->prepare("SELECT post_id FROM post_tags WHERE tag_id=$tagId");
        $sth->execute();
        $nRows = $sth->rowCount();
      }
      
      // get max number of pages and assign to $pages
      $this->pages = ceil($nRows/$pageSize);
    }
  
    // create fetch function to retrieve posts. returns string of posts.
    public function fetch($obj){
    
      // define gobal variable within fuction scope
      global $dbh;
      
      // iterate $obj and create variables
      foreach($obj as $key => $value){
        $$key = $value;
      }
      
      // check if page number is less than one. if it is, set it to 1.
      if (!isset($page) || $page < 1) {
        $page = 1;
      }
      
      // set up variables for limiting query
      $startResult = ($page - 1) * $pageSize;
      
      // check if startResult is less than 0 (this means there are less posts
      if($startResult < 0){
        $startResult = 1;
      }
      
      $tagSearch = $dbh->prepare("SELECT id FROM tags WHERE name='$tag'");
      $tagSearch->execute();
      $tagFetch = $tagSearch->fetch(PDO::FETCH_ASSOC);
      $tagId = $tagFetch['id'];
      
      // query the database. if $tag is not empty, fetch by tag id.
      if(empty($tag)){
        $qry2 = $dbh->prepare("SELECT * FROM posts WHERE draft='false' ORDER BY timestamp DESC LIMIT $startResult, $pageSize");
        $qry2->execute();
      } else {
        $qry = $dbh->prepare("SELECT post_id FROM post_tags WHERE tag_id=$tagId");
        $qry->execute();
        $postIds = '('.implode(',',$qry->fetchAll(PDO::FETCH_COLUMN, 0)).')';
        $qry2 = $dbh->prepare("SELECT * FROM posts WHERE draft='false' AND id IN $postIds ORDER BY timestamp DESC LIMIT $startResult, $pageSize");
        $qry2->execute();
      }
      
      // fetch posts and reverse their order
      $result = $qry2->fetchAll();
      
      if($result){
        // create empty string
        $postsString = '';
        
        // loop through query array
        foreach($result as $post){
          // main post container
          $postsString .= '<'.$containerType.' class="'.$container.'">';
          
          // check if user wants header surrounding title. if so, create the header container
          if($header){
            $postsString .= '<'.$headerContainerType.' class="'.$headerContainer.'">';
          }
          
          // date container
          $postsString .= '<'.$dateContainerType.' class="'.$dateContainer.'" title="'.$post["date"].'">';
          
          // check date formate. if 'ago', use timeago format. otherwise use plain format
          if($dateFormat === 'ago'){
            $postsString .= ''.ago($post["timestamp"]);
          } else {
            $postsString .= $post["date"];
          }
          
          // date container
          $postsString .= '</'.$dateContainerType.'>';
          
          // post title container
          $postsString .= '<'.$titleContainerType.' class="'.$titleContainer.'">
            <a href="'.$href.'post/';
      
      if($postLink === 'id'){
        $postsString .= $post["id"];
      } else {
        $postsString .= $post["title_url"];
      }
      
      $postsString .= '">'.$post["title"].'</a>
      </'.$titleContainerType.'>';
          
          // header container
          if($header){
            $postsString .= '</'.$headerContainerType.'>';
          }
          
          // content container
          $postsString .= '<'.$contentContainerType.' class="'.$contentContainer.'">
            '.truncate($post["content"], $truncateLength).'<a class="readMore" href="'.$href.'post/'.$post["title_url"].'">Continue reading&rarr;</a>
          </'.$contentContainerType.'>';
          
          // main post container
          $postsString .= '</'.$containerType.'>';
        }
        
        // return complete string with posts
        echo $postsString;
      } else {
      
        // echo message if no posts were found
        echo 'There are no posts.';
      }
    }
    
    public function tag($obj){
    
      // define global variable within function scope
      global $dbh;
      
      // iterate through $obj and create variables
      foreach($obj as $key => $value){
        $$key = $value;
      }
      
      // create empty string
      $postTag = '';
      
       // fetch tag from db based on id
      $qry = $dbh->prepare("SELECT * FROM tags WHERE name='$tag'");
      $qry->execute();
      $result = $qry->fetch(PDO::FETCH_ASSOC);
      
      if($plaintext && $format==='name'){
        $postTag = $result["name"];
      } else if($plaintext && $format==='display'){
        $postTag = $result["display"];
      } else {
        // create element for tag
        $postTag .= '<'.$containerType;
        
        if($link){
          $postTag .= ' href="'.$href.$result["name"].'"';
        }
        
        if($container){
          $postTag .= ' class="'.$container.'"';
        }
        
        $postTag .= '>';
        
        if($format === 'name'){
          $postTag .= $result["name"];
        } else {
          $postTag .= $result["display"];
        }
        
        $postTag .= '</'.$containerType.'>';
      }
      
      echo $postTag;
    }
  }
  
  // this class is for fetching data on specific posts (for individual post pages)
  class post {
  
    // create properties for later definition
    public $id;
    public $title;
    public $title_url;
    public $date;
    public $timestamp;
    public $ago;
    public $content;
    public $tags;
    public $next;
    public $prev;
    
    function __isset($name) {
      if( $name == "id" || $name == "title" || $name == "title_url" ||
        $name == "date" || $name == "timestamp" || $name == "ago" ||
        $name == "content" || $name == "tags" || $name == "next" ||
        $name == "prev"
      ){
        return true;
      }
    }

    // create fetch function. this either outputs an error if no post is found or defines properties to be echoed on the page.
    public function fetch($toFetch, $getVar){
    
      // define dbh inside class
      global $dbh;
      
      // check whether id number or url encoded title is used to fetch from db
      if($toFetch === 'id'){
      
        //query with id number
        $qry = $dbh->prepare("SELECT * FROM posts WHERE draft='false' AND id=$getVar");
        $qry->execute();
        $result = $qry->fetch(PDO::FETCH_ASSOC);
        
      } else {
      
        // query with url encoded title
        $qry = $dbh->prepare("SELECT * FROM posts WHERE draft='false' AND title_url='".$getVar."'");
        $qry->execute();
        $result = $qry->fetch(PDO::FETCH_ASSOC);
      }
      // check results
      if(!($result)){
      
        // echo error if no match is found
        echo 'Post not found.';
        
      // make sure only one row was returned
      } else {
      
        // fetch array from query
        $arr = $result;
        
        $nextId = $arr["id"] + 1;
        
        $nextPost = $dbh->prepare("SELECT * FROM posts WHERE draft='false' AND id=".$nextId);
        $nextPost->execute();
        $next = $nextPost->fetch(PDO::FETCH_ASSOC);
        
        $prevId = $arr["id"] - 1;
        
        $prevPost = $dbh->prepare("SELECT * FROM posts WHERE draft='false' AND id=".$prevId);
        $prevPost->execute();
        $prev = $prevPost->fetch(PDO::FETCH_ASSOC);
        
        //define proprties from array results
        $this->id = $arr["id"];
        $this->title = $arr["title"];
        $this->title_url = $arr["title_url"];
        $this->date = $arr["date"];
        $this->timestamp = $arr["timestamp"];
        $this->ago = ago($this->timestamp);
        $this->content = $arr["content"];
        $this->tags = explode(",",$arr["tags"]);
        $this->next = $next;
        $this->prev = $prev;
      }
    }
    
    // create the function that grabs the tag names from ther ids
    public function tags($obj){
    
      // define global variable within function scope
      global $dbh;
      
      // iterate through $obj and create variables
      foreach($obj as $key => $value){
        $$key = $value;
      }
      
      // create empty string
      $postTags = '';
      
      // get last value of tags array to check whether to append comma (if $comma is true)
      $last = end($this->tags);
      
      // iterate through tags array
      foreach($this->tags as $tag){
      
        // fetch tag from db based on id
        $qry = $dbh->prepare("SELECT * FROM tags WHERE id=$tag");
        $qry->execute();
        $result = $qry->fetch(PDO::FETCH_ASSOC);
        
        // create element for tags
        $postTags .= '<'.$containerType;
        
        if($link){
          $postTags .= ' href="'.$href.$result["name"].'"';
        }
        
        if($container){
          $postTags .= ' class="'.$container.'"';
        }
        
        $postTags .= '>';
        
        if($format === 'name'){
          $postTags .= $result["name"];
        } else {
          $postTags .= $result["display"];
        }
        
        $postTags .= '</'.$containerType.'>';
        
        if($comma && $tag !== $last){
          $postTags .= ", ";
        }
      }
      
      echo $postTags;
    }
  }
  
  // assign variables
  $posts = new posts();
  $post = new post();
?>