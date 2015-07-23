<html>
<head>
{foreach $METAS as $meta}
<meta name="{$meta@key}" content="{$meta}">
{/foreach}

<title>{$TITLE}</title>

{foreach $header->css as $link}
<link  name={$link@key} rel="stylesheet" type="text/css" href="{$link.link}"/>
{/foreach} 

<script type="text/javascript">
var CONTEXT_PATH = '{$smarty.const.CONTEXT_PATH}';
var RX_MODE_DEBUG = !!('{$smarty.const.RX_MODE_DEBUG}');
var RELOAD_VERSION = ('{$smarty.const.RELOAD_VERSION}');
{foreach $header->const as $const}var {$const@key} = '{$const}';{/foreach}
</script>
<script>
/*
["checking","error","noupdate","downloading","progress","updateready"].map(function(key){
	console.info("attaching",key)
	window.applicationCache.addEventListener(key, function(){ console.info("-----",key)})
}, false);
try{
window.applicationCache.update()
} catch(e){
	console.info("ee",e)
}
*/
</script>
</head>
<body>

{include file="$BODY_FILES" title="body"}

<div id="templates" style="height:0px;"></div>
<div id="page_json" data-value='{$page_json}'></div>
<div id="script_logs"></div>

</body>

{if $header->const.RX_JS_MERGE}
   	{foreach $header->scripts_bundle as $file}
		{if $file.remote}
			<script name="{$file@key}"src="{$file.link}" type="text/javascript"></script>
		{else}
			<script name="{$file@key}"src="{$file.link}&_={$smarty.const.RELOAD_VERSION}" type="text/javascript"></script>
		{/if}
   	{/foreach}
{else}
   	{foreach $header->scripts as $src}
		{if $file.remote}
			<script name={$src@key} src="{$src.link}" type="text/javascript"></script>
		{else}
			<script name={$src@key} src="{$src.link}?_={$smarty.const.RELOAD_VERSION}" type="text/javascript"></script>
		{/if}
	{/foreach}
{/if}

{if $smarty.const.RX_MODE_MAGIC}
<style>
.rx_debuggger{
	position:fixed; right:-10px; width:20px; height:20px;
	bottom:40px;
}
.rx_debuggger:hover{
	right:10px;
}
</style>
<div class="rx_debuggger" style="">
	<a href="?ModPagespeed=off&_={$smarty.const.RELOAD_VERSION}">@RELOAD</a>
</div>
{/if}

</html>
