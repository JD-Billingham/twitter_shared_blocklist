<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>THE BLOCK BOT</title>
	<style type="text/css">
		body, html { 
		width: 1300px;
   	 	height: 820px;
    	margin: 0;
		font-family:sans-serif,helvetica,arial; }
		#topbox { border: 1px solid grey}
		#middlebox { border: 1px solid grey}
		#bottombox { border: 1px solid grey}
      	img {border-width: 0}
	</style>
	</style>
	<script src="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
	<script type="text/javascript">
		dojo.require("dijit.form.Button");
		dojo.require("dijit.layout.TabContainer");
		dojo.require("dijit.layout.ContentPane");
		dojo.require("dijit.form.CheckBox");
	</script>
	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/dojo/1.8/dijit/themes/claro/claro.css"/>
</head>
<body class="claro">
    <div data-dojo-type="dijit/layout/BorderContainer" style="width: 100%; height: 100%;">
    <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region:'left'"></div>
    <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region:'top'">
    		<h1>&nbsp;&nbsp;&nbsp;<a href="http://www.theblockbot.com/" style="text-decoration: none"><?php print_r($title);?></a></h1></div>
    
<div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region:'center'">
 	<div data-dojo-type="dijit/layout/ContentPane" <?php print_r($visibility);?>>   
    Click on the Twitter icon at the bottom of the page and authorise <a href="https://twitter.com/The_Block_Bot">The Block Bot</a> to block on your behalf. 
    If you are having issues try <a href='./clearsessions.php'>clearing your session</a>.
        Links: <a href='http://www.theblockbot.com/?page_id=2'>More Information (Privacy/How it Works)</a> &amp;
        
Contact @<a href='http://twitter.com/The_Block_Bot'>The_Block_Bot</a> or <a href='http://atheismplus.com/forums/viewtopic.php?f=13&t=3896'>the A+ forum</a> if you are
blocked and want to complain ... Or if you want to be a blocker and have access to add to the blocklist then ask and we'll add you, assuming you are not on the list!
</br></br>
There are three levels to choose from :-
</br></br><b>→ Level 1 blocking:</b> this blocks only the worst of the worst. These are the really nasty ones. 
Both “sides” across the Deep Rifts™ will hopefully agree these need to be blocked. 
Accounts that spam extremely abusive messages to people with the intent only of hurting them with not a hint of “disagreement”. 
D0x’ers who want to drop information on fellow atheists in order to scare them off the internet or have real life effects on their well-being. 
Stalkers that create sock-accounts to inject themselves into your time line to get a response from you or imposters pretending to be you.
</br></br><b>→ Level 2 blocking:</b> these are the abusive subset of anti-feminists, MRAs, or all-round assholes who think nothing of tweeting their much loved 
photoshopped pictures, memes and other wonderful media directly into your timeline to get attention (Listen to Meee!!1!). 
This level also includes the “parody” accounts, if you have better things to do with your life than “disagree” on Twitter with a parody of yourself that 
seems to have suffered a frontal lobotomy.
<p style="text-align: center;"><i>Level 2 blocking includes all members of level 1.</i></p>
<b>→ Level 3 blocking:</b> these are the merely annoying and irritating Twitterers who trot out the A+ arguments to avoid at a moment’s notice,
 and show no signs of giving them up until you pry them from their cold, dead hands. 
 Given that is not a practical option, how about blocking them and avoiding tedious exchanges? 
 This is the 100% frozen peach option… These from time to time leap to level 1/2 so why take the risk?
<p style="text-align: center;"><i>Level 3 blocking includes all members of levels 1 and 2.</i></p>
    </div>
    <?php print_r($content); ?> 
</div>
    <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region:'trailing'"  <?php print_r($visibility);?>>  
<a href="http://www.zazzle.com/almostdiamonds"><img src="http://freethoughtblogs.com/almostdiamonds/files/2013/01/FreezePeachPlain.png" height="200" width="200"></a>
<br><p style="font-weight:bold;font-size:x-small;">Guaranteed 100% Pure Frozen Peach &#8482;</p>
<p style="font-weight:bold;font-size:xx-small;"><a rel="license" href="http://creativecommons.org/licenses/by-nc/2.0/uk/deed.en_GB">
<a rel="license" href="http://creativecommons.org/licenses/by-nc/2.0/uk/deed.en_GB">Creative Commons Attribution<br>NonCommercial License</a>
<br></br>Original from; <a href="http://freethoughtblogs.com/almostdiamonds/">Almost Diamonds</a></p></div>
    

</div>
</body>
</html>
