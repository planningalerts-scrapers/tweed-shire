<?php
require_once 'vendor/autoload.php';
require_once 'vendor/openaustralia/scraperwiki/scraperwiki.php';

// Tweed Shire Council Development Applications scraper
// (ICON Software Solutions PlanningXchange)
// Sourced from https://s1.tweed.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx
// Formatted for http://www.planningalerts.org.au/

date_default_timezone_set('Australia/Sydney');

$date_format = 'Y-m-d';
$cookie_file = '/tmp/cookies.txt';
$comment_url = 'mailto:tsc@tweed.nsw.gov.au';
$terms_url = 'http://www.tweed.nsw.gov.au/DisclaimerMasterView.aspx';
$rss_feed = 'https://s1.tweed.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx?d=thismonth&k=LodgementDate&t=DA,CDC&o=rss';

print "Scraping s1.tweed.nsw.gov.au...\n";

//accept_terms($terms_url, $cookie_file);

// Download and parse RSS feed (last 14 days of applications)
$curl = curl_init($rss_feed);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; PlanningAlerts/0.1; +http://www.planningalerts.org.au/)");
$rss_response = curl_exec($curl);
curl_close($curl);

$rss_response = preg_replace('/utf-16/i', 'utf-8', $rss_response);
$rss = simplexml_load_string($rss_response);

// Iterate through each application
foreach ($rss->channel->item as $item)
{
    // RSS title appears to be the council reference
    $rss_title = explode(' - ', $item->title);
    $council_reference = trim($rss_title[0]);

    // RSS description appears to be the address followed by the actual description
    $rss_description = preg_split('/\./', $item->description, 2);
    $address = trim($rss_description[0]);
    $address = trim(preg_replace('/\s+/', ' ', $address));
    $description = trim($item->category . ' -' . $rss_description[1]);

    $info_url = 'https://s1.tweed.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx' . trim($item->link);

    $date_scraped = date($date_format);
    $date_received = date($date_format, strtotime($item->pubDate));

    $application = array(
        'council_reference' => $council_reference,
        'description' => $description,
        'date_received' => $date_received,
        'address' => $address,
        'date_scraped' => $date_scraped,
        'info_url' => $info_url
        //'on_notice_from' => $on_notice_from,
        //'on_notice_to' => $on_notice_to
    );

    print ("Saving record " .$application['council_reference']. " - " .$application['address']. "\n");
    scraperwiki::save(array('council_reference'), $application);
}

function accept_terms($terms_url, $cookie_file)
{
    $curl = curl_init($terms_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
    $terms_response = curl_exec($curl);
    curl_close($curl);

    preg_match('/<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="(.*)" \/>/', $terms_response, $viewstate_matches);
    $viewstate = $viewstate_matches[1];

    preg_match('/<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="(.*)" \/>/', $terms_response, $eventvalidation_matches);
    $eventvalidation = $eventvalidation_matches[1];

    $postfields = array();
    $postfields['__VIEWSTATE'] = $viewstate;
    $postfields['__EVENTVALIDATION'] = $eventvalidation;

    $postfields['ctl00$cph_content$butAccept'] = 'I Accept';

    $curl = curl_init($terms_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
    curl_exec($curl);
    curl_close($curl);
}
?>
