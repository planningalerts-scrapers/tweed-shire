<?php
require 'scraperwiki.php';

require 'scraperwiki/simple_html_dom.php';

// Tweed Shire Council Development Applications scraper
// (ICON Software Solutions PlanningXchange)
// Sourced from http://s1.tweed.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx
// Formatted for http://www.planningalerts.org.au/

date_default_timezone_set('Australia/Sydney');

$date_format = 'Y-m-d';
$cookie_file = '/tmp/cookies.txt';
$comment_url = 'mailto:tsc@tweed.nsw.gov.au';
$terms_url = 'http://www.tweed.nsw.gov.au/DisclaimerMasterView.aspx';
$rss_feed = 'http://s1.tweed.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx?d=thismonth&k=LodgementDate&t=DA,CDC&o=rss';

print "Scraping s1.tweed.nsw.gov.au...\n";

//accept_terms($terms_url, $cookie_file);

// Download and parse RSS feed (last 14 days of applications)
$curl = curl_init($rss_feed);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; PlanningAlerts/0.1; +http://www.planningalerts.org.au/)");
$rss_response = curl_exec($curl);
curl_close($curl);

$rss_response = preg_replace('/utf-16/i', 'utf-8', $rss_response);
$rss = simplexml_load_string($rss_response);

// Iterate through each application
foreach ($rss->channel->item as $item)
{
    // RSS title appears to be the council reference
    $council_reference = trim($item->title);

    print "Found $council_reference...\n";

    // RSS description appears to be the address followed by the actual description
    $rss_description = preg_split('/\./', $item->description, 2);
    $address = trim($rss_description[0]);
    $description = trim($rss_description[1]);

    $info_url = 'http://s1.tweed.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx' . trim($item->link);

    $date_scraped = date($date_format);
    $date_received = date($date_format, strtotime($item->pubDate));

    $application = array(
        'council_reference' => $council_reference,
        'address' => $address,
        'description' => $description,
        'info_url' => $info_url,
        'comment_url' => $comment_url . 'Application Enquiry: ' . $council_reference,
        'date_scraped' => $date_scraped,
        'date_received' => $date_received //,
        //'on_notice_from' => $on_notice_from,
        //'on_notice_to' => $on_notice_to
    );

    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $application['council_reference'] . "'");
    if (sizeof($existingRecords) == 0)
    {
        scraperwiki::save(array('council_reference'), $application);
    }
    else
    {
        print ("Skipping already saved record " . $application['council_reference'] . "\n");
    }
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
