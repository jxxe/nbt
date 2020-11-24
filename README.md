# NBT
The Named Binary Tag file format is used by Minecraft to store data.
<br>This class allows for simple reading of these files into an associative array.

After requiring `nbt.php`, it's a breeze to turn binary data into JSON:
```php
$reader = new NBT;
$reader->loadString($data);
echo json_encode($reader->result);
```
