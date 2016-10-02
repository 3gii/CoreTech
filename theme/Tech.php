<html>
<head>
<title>{$title}</title>
</head>
<body>
 <h1> {$s}</h1>
 <br/>
   {if $arr}
   {foreach from=$arr item=arr}
   {$arr}
   {/foreach}
   {/if}
   <br/>
     {if $User}
   {foreach from=$User item=user}
   <tr><td>id:</td><td>{$user.id}</td></tr>
   <tr><td>name:</td><td>{$user.name}</td></tr>
   <tr><td>age:</td><td>{$user.age}</td></tr><br/>
   {/foreach}
   {/if}
  <hr/>
  <h4>测试一下初始值</h4><br/>
  <h4>{$sitename}--{$keywords}</h4>
</body>
</html>