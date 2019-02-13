<?php
//items4.php
//create items for sale in taco truck
$myItem = new Item(1,"Taco","Ground beef, lettuce, and shredded cheese in a crisp corn shell!",3.50);
$config->items[] = $myItem;

$myItem = new Item(2,"Burrito","Loaded with cheese, rice, beans and shredded beef!",6.25);
$config->items[] = $myItem;

$myItem = new Item(3,"Quesadilla","Full of cheese, spices and grilled onions and peppers!",8.95);
$config->items[] = $myItem;

$myItem = new Item(4,"Enchilada","Shredded chicken and cheese smothered in our house-made sauce!",5.95);
$config->items[] = $myItem;

$myItem = new Item(5,"Nachos","Crispy chips, melty cheese and fresh toppings can't be beat!",7.50);
$config->items[] = $myItem;

class Item //create Item class
{
    public $ID = 0;
    public $Name = '';
    public $Description = '';
    public $Price = 0;
        
    public function __construct($ID,$Name,$Description,$Price) //Item constructor
    {
        $this->ID = $ID;
        $this->Name = $Name;
        $this->Description = $Description;
        $this->Price = $Price;    
    }#end Item constructor
}#end Item class











