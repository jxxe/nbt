I coded this project a while ago when I didn't really know what I was doing, and it breaks with a lot of edge-cases. I could fix it, or I could just tell you to use [BrandonXLF/NBT.php](https://github.com/BrandonXLF/NBT.php) which outputs data in the exact same format and actually works.

If you feel like fixing this one, feel free to make a pull request!

# NBT Reader for PHP
The Named Binary Tag file format is used by Minecraft to store data.
<br>This class allows for simple reading of these files into an associative array.

After requiring `nbt.php`, it's a breeze to turn binary data into JSON:
```php
$reader = new NBT;
$reader->loadString($data);
echo json_encode($reader->result);
```
