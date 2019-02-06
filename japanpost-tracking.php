<?php

function getTrackingInfo(string $inquiryNumber)
{
  $result = [];

  $inquiryNumber = str_replace('-', '', $inquiryNumber);
  $trackingPageUrl = sprintf('https://trackings.post.japanpost.jp/services/srv/search/direct?reqCodeNo1=%s&locale=ja', $inquiryNumber);

  $trackingPage = file_get_contents($trackingPageUrl);
  $trackingPage = preg_replace('/<script type="text\/javascript">.*<\/script>/s', '', $trackingPage);
  $trackingPage = preg_replace('/<img [^>]+\/>/s', '', $trackingPage);
  $dom = new DOMDocument();
  $dom->loadHTML($trackingPage);

  $xpath = new DOMXPath($dom);

  $result['inquiryNumber'] = $xpath->evaluate('string(//form[@name="srv_searchActionForm"]//table[@summary="配達状況詳細"]/tr[2]/td[1])');
  $result['itemType'] = $xpath->evaluate('string(//form[@name="srv_searchActionForm"]//table[@summary="配達状況詳細"]/tr[2]/td[2])');

  $historyEntries = $xpath->query('//form[@name="srv_searchActionForm"]//table[@summary="履歴情報"]/tr');
  for($i = 2; $i < $historyEntries->length; $i += 2) {
    $entry = [];

    $entry['zipCode'] = $xpath->evaluate('string(./td[1])', $historyEntries->item($i + 1));;

    $node = $historyEntries->item($i);
    $entry['date'] = $xpath->evaluate('string(./td[1])', $node);
    $entry['action'] = $xpath->evaluate('string(./td[2])', $node);
    $entry['detail'] = $xpath->evaluate('string(./td[3])', $node);
    $entry['office'] = $xpath->evaluate('string(./td[4])', $node);
    $entry['prefecture'] = $xpath->evaluate('string(./td[5])', $node);

    $result['history'][] = $entry;
  }

  $contactEntries = $xpath->query('//form[@name="srv_searchActionForm"]//table[@summary="窓口店"]/tr');
  for($i = 1; $i < $contactEntries->length; $i++) {
    $entry = [];

    $node = $contactEntries->item($i);
    $entry['caseClass'] = $xpath->evaluate('string(./td[1])', $node);
    $entry['office'] = $xpath->evaluate('string(./td[2]/a[1])', $node);
    $entry['phoneNumber'] = $xpath->evaluate('string(./td[3])', $node);

    $result['contact'][] = $entry;
  }

  return $result;
}
