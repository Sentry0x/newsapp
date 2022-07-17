<?php
include "INewsDB.class.php";
class NewsDB implements INewsDB, IteratorAggregate{
  const DB_NAME = 'news.db';
  protected $_db;
  private $items = [];
  function __construct(){
    $this->_db = new PDO('sqlite:'.self::DB_NAME);
    $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(filesize(self::DB_NAME)<=0){
      try{
      $this->_db->beginTransaction();
      $sql = "CREATE TABLE msgs(
                              id INTEGER PRIMARY KEY AUTOINCREMENT,
                              title TEXT,
                              category INTEGER,
                              description TEXT,
                              source TEXT,
                              datetime INTEGER
                          )";
      $this->_db->exec($sql);
      $sql = "CREATE TABLE category(
                                  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                  name TEXT
                              )";
      $this->_db->exec($sql);
      $sql = "INSERT INTO category(id, name)
                  SELECT 1 as id, 'Политика' as name
                  UNION SELECT 2 as id, 'Культура' as name
                  UNION SELECT 3 as id, 'Спорт' as name";
      $this->_db->exec($sql);
      $this->_db->commit();
    } catch(PDOException $e) { 
        $this->_db->rollBack();
        echo $e->getCode() .":". $e->getMessage();
      }
      
    }
    $this->getCategories();
  }
  function __destruct(){
    unset($this->_db);
  }
  private function getCategories(){
    try{
    $sql = "SELECT id,name from category";
    $result = $this->_db->query($sql);
    if (!is_object($result)) 
      throw new PDOException('Не удалось извлечь категории');
    $arr = [];
    while($row = $result->fetch(PDO::FETCH_ASSOC)){
        $arr[$row['id']] = $row['name'];}
    $this->items = $arr;
    return $this->items;
      }catch(PDOException $e){
        echo $e->getMessage();
        return false;}
  }
  function getIterator() : traversable{
    return new ArrayIterator($this->items); 
  }
  function saveNews($title, $category, $description, $source){
    $dt = time();
    $title = $this->clearData($title);
    $description = $this->clearData($description);
    $source = $this->clearData($source);
    $sql = "INSERT INTO msgs(title, category, description, source, datetime)
                VALUES($title, $category, $description, $source, $dt)";
    $ret = $this->_db->exec($sql);
    if($ret === false)
      return false;
    return true;	
  }	
  protected function db2Arr($data){
    $arr = [];
    while($row = $data->fetch(PDO::FETCH_ASSOC))
      $arr[] = $row;
    return $arr;
  }
  public function getNews(){
    try{
      $sql = "SELECT msgs.id as id, title, category.name as category, description, source, datetime 
              FROM msgs, category
              WHERE category.id = msgs.category
              ORDER BY msgs.id DESC";
      $result = $this->_db->query($sql);
      if (!is_object($result)) 
        throw new PDOException('Не удалось извлечь новости');
      return $this->db2Arr($result);
    }catch(Exception $e){
      echo $e->getMessage();
      return false;
    }
  }	
  public function deleteNews($id){
    try{
      $sql = "DELETE FROM msgs WHERE id = $id";
      $result = $this->_db->exec($sql);
      if ($result === false) 
        throw new PDOException('не удалсь удалить');
      return true;
    }catch(PDOException $e){
      echo $e->getMessage();
      return false;
    }
  }
  function clearData($data){
      return $this->_db->quote($data); 
  }	
}