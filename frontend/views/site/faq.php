<?php
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 */
$this->title = 'Frequently Asked Questions';
$this->params['wrapClass'] = 'wrap-white';

$this->registerJs("

")
?>
<div class="site-faq">
<h1 class="text-muted"><?= Html::encode($this->title) ?></h1>
<div class="row">
<div class="col-sm-12"><div id="faqAffix" class="faq-affix">
<div class="panel panel-default">
  <div class="panel-heading">FAQ Contents</div>
  <div class="panel-body">
<a href="#what-is-site">What is this site?</a>
<a href="#is-it-free">Is it free?</a>
<a href="#supported-comics">What comics do you support?</a>
<a href="#add-comics">What can I do to get a comic I like put into my email?</a>
<a href="#why">Why did you create this site?</a>
<a href="#cookies">Does this site use cookies?</a>
<a href="#profit">Does this site earn money?</a>
<a href="#copyright">Are you breaking copyright?</a>
<a href="#use-service">I am a comic author...can I use your service?</a>
<a href="#earn-money">Can I earn money from my cartoon on your network?</a>
<a href="#dmca">I want to sue/DMCA you!</a>
<a href="#terms">Terms & Conditions</a>
  </div>
</div>

</div></div>
<div class="col-sm-36">

<div class="faq-item">
<span id="what-is-site" class="head-anchor"></span>
<h2>What is this site?</h2>
<p>This site is a new comic destribution service I made for my own personal use which I have decided to release to the general internet community.</p>
<p>Essentially it allows you batch, for free, your daily comics across the internet and have them sent directly to your inbox.</p>
<p>You can look to the <a href="<?= Url::to(['/']) ?>">homepage for a more flambouyant</a> edition of what was just said.</p>
</div>

<div class="faq-item">
<span id="is-it-free" class="head-anchor"></span>
<h2>Is it free?</h2>
<p>Yes.</p>
<p>I have no intention on charging for this, nor ever.</p>
</div>

<div class="faq-item">
<span id="supported-comics" class="head-anchor"></span>
<h2>What comics do you support?</h2>
<p>Currently only my personal favourites:</p>
<ul>
<li>Garfield</li>
<li>U.S.Acres</li>
<li>Dilbert</li>
<li>xkcd</li>
</ul>
<p>However, it is easy to add more, see the FAQ below...</p>
</div>

<div class="faq-item">
<span id="add-comics" class="head-anchor"></span>
<h2>What can I do to get a comic I like put into my email?</h2>
<p>You can do it one of two ways, depending on who you are:</p>
<ul>
<li><a href="mailto:before_i_sleep@hotmail.co.uk?subject=Plese Add A Comic I Like">You can email me at my public inbox</a></li>
<li><a href="https://github.com/Sammaye/comics.ly/issues">You can open an issue on GitHub</a></li>
</ul>
<p>The only information I need is the name of the comic and its homepage URL.</p>
<p>There may be some comics that, due to their setup, might not be crawlable. You will be notified if the comic you want 
is one of these, however, I must state in that case it is unlikely I can add it to your email.</p>
</div>

<div class="faq-item">
<span id="why" class="head-anchor"></span>
<h2>Why did you create this site?</h2>
<p>Personal use.</p>
<p>I am a big fan of certain comics:- Dilbert, Garfield, US Acres and xkcd, to name a few. When I tried to "subscribe" I found that either they didn't have a function 
to do so or it costed money.</p>
<p>Many sites also implemented extremely intrusive advertising if you didn't cough up. GoComics was one which would physically disjoint and shift your screen. 
I also suffer virus warnings on Dilbert regularly from their popup ads.</p>
<p>Fair enough, I could use an Ad Blocker, but then how is that any different to just making this service in terms of "support" (remember that ads pay money). 
Not only that but I still have all those tabs and websites to check every day!</p>
<p><b>Why not just make this service and make my life easier by spending maybe a week of my time making it?</b></p>
<p>I am not the sort to shy from paying money but I find it difficult to see how the &dollar;11 (&pound;6.87) a year I pay to GoComics really helps the cartoonists 
and not them instead.</p> 
<p>It most likely costs about that to keep the servers up.</p>
<p>So, in general, I just decided to break the mould and make this service.</p>
</div>

<div class="faq-item">
<span id="cookies" class="head-anchor"></span>
<h2>Does this site use cookies?</h2>
<p>Name me a site that allows login and a user account section that doesn't and is slightly secure...</p>
<p>In other words: yes.</p>
<p>The cookies used on this site are:</p>
<ul>
<li>login cookies, in which case we may use two individual cookies to identify you as who you say you are</li>
<li>analytical cookies, namely for Google Analytics</li>
<li>third party cookies that are out of my control, for example: YouTube could be used on this site and they in turn use cookies of their own</li>
</ul>
<p>Analytical cookies are unavoidable. They are still the only reliable way to provide analytics for webmasters like myself. They help me to make sure I give you a decent experience.</p>
<p>A cookie will never be used to hold peronal information about you and your account on this site.</p>
</div>

<div class="faq-item">
<span id="profit" class="head-anchor"></span>
<h2>Does this site earn money?</h2>
<p>No.</p>
<p>This site is non-profit. It makes no money from its services, no even in advertising.</p>
<p>As a fore-warning, that could change to include non-intrusive advertising at a later date if this site expands to take a significant budget or 
third party pressure causes me to pay a significant amount.</p
<p>I intend to never make money (for site maintenance or third party payments) from this site by charging for it.</p>
</div>

<div class="faq-item">
<span id="copyright" class="head-anchor"></span>
<h2>Are you breaking copyright?</h2>
<p>I am not pretending to have ownership over these cartoons nor do I claim to be their author. Instead I am just an average guy who wanted to get his daily dose of 
cartoon fever in a easy to manage email subscription.</p>
<p>I clearly and visually label the name of the cartoons and their authors, including the registered homepage for that cartoon.</p>
<p>As far as I know that comes under the rules as quoting someone.</p>
</div>

<div class="faq-item">
<span id="use-service" class="head-anchor"></span>
<h2>I am a comic author...can I use your service?</h2>
<p><b>That would be...AWESOME!</b></p>
<p>However, the necessary parts are not yet there. I would have to implement a method by which you can feed a list into my service to mail to.</p>
<p>Since normal internet laws of their respective countries do not allow you randomly giving me peoples' email addresses you would need a way of feeding 
a forgettable list of emails into my system to be mailed.</p>
<p>Also customisation features would be needed.</p>
<p>If you are interested in using this distribution service yourself <a href="mailto:before_i_sleep@hotmail.co.uk?subject=I am interested in using your service">let 
me know by emailing me</a> and I would be quite excited to work wth you.</p>
</div>

<div class="faq-item">
<span id="earn-money" class="head-anchor"></span>
<h2>Can I earn money from my cartoon on your network?</h2>
<p>Not currently, however, if this were to become serious I would look into allowing royalties and for cartoonists to advertise their books/merchandise.</p>
<p>I am reluctant to allow paid ads within emails like they do in Dilbert emails since those ads really suck and have no reference to those who actually 
read Dilbert. I mean seriously; why would I want to see some random, spammy looking, site advertising holidays to Antarctica?</p>
</div>

<div class="faq-item">
<span id="dmca" class="head-anchor"></span>
<h2>I want to sue/DMCA you!</h2>
<p>This is nopthing more than a pesonal project that I have shared to others who might be interested in it, however, if you really want 
to go ahead with this you <a href="mailto:before_i_sleep@hotmail.co.uk?subject=DMCA or Sueing">can email me about it</a>.</p>
</div>

<div class="faq-item faq-tac">
<span id="terms" class="head-anchor"></span>
<h2>Terms & Conditions</h2>
<p>These are quite simple.</p>
<h4>Your Email Address</h4>
<p>When you sign upto this service you agree to giving me your email address and that I may hold it in my database and send you emails.</p>
<p>The emails you agree to receive are that of your subscription.</p>
<p>If I decide to change the emails you recieve (say, to add a newsletter) I am obliged give you prior notice and the ability to opt-out/unsubscribe.</p>
<h4>Cancelling</h4>
<p>You can cancel at any time however, you must allow me time to run a script to scrub you from my database fully, the bigger a user you are the longer it will take.</p>
<p>This process should not take more than 48 hours however, if after that time you still have not been deleted please 
<a href="mailto:before_i_sleep@hotmail.co.uk?subject=My account is not deleting">let me know via email</a> and I will endeavour to solve the problem.</p>
</div>

</div>
</div>
