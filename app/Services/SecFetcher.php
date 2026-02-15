<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Filing;
use Symfony\Component\DomCrawler\Crawler;
use Spatie\Browsershot\Browsershot;
use App\Services\AiProcessor;

// 3 tier fetcher
class SecFetcher{

  // Fetch via Api as tier 1
  private function fetchFromAtom(): string {
      $url ='https://www.sec.gov/cgi-bin/browse-edgar?action=getcurrent&type=&company=&dateb=&owner=include&start=0&count=40&output=atom'; 
      $response = Http::withHeaders([
      'User-Agent' => config('services.sec.user_agent')
    ])->get($url);

    $xml = simplexml_load_string($response->body());

    foreach ($xml->entry as $entry){
      Filing::updateOrCreate(
        ['link' => (string) $entry->link['href']],
        
        [
          'title' => (string) $entry->title,
          'category' => (string) $entry->category['term'],
          'filed_at' => (string) $entry->updated,
        ]
      );
    }

    return "Successfully saved " . count($xml->entry) . " filings.";
  }

  // Helper function for tier 2 and 3
  private function saveFilingsFromHtml($html): void {
    $crawler = new Crawler($html);

    $crawler->filter('table.tableFile2 tr')->slice(1)->each(function ($node){
      $cols = $node->filter('td');

      if ($cols->count() >= 4){
        Filing::updateOrCreate(
          ["link" => 'https://www.sec.gov' . $cols->eq(1)->filter('a')->attr('href')],
          [
            'title' => $cols->eq(2)->text(),
            'category' => $cols->eq(0)->text(),
            'filed_at' => $cols->eq(3)->text(),
          ]
        );
      }
    });
  }

  // Tier 2 webscraping
  private function fetchViaScraping(): void {
    $url = 'https://www.sec.gov/cgi-bin/browse-edgar?action=getcurrent&type=&company=&dateb=&owner=include&start=0&count=40';
    $response = Http::withHeaders([
      'User-Agent' => config('services.sec.user_agent')
    ])->get($url);

    $this->saveFilingsFromHtml($response->body());

  }

  // Tier 3 using Browser
  private function fetchViaBrowser(): void {
    $url = 'https://www.sec.gov/cgi-bin/browse-edgar?action=getcurrent&count=40';

    $html = Browsershot::url($url)
        ->noSandbox()
        ->setOption('args', ['--disable-setuid-sandbox', '--disable-dev-shm-usage'])
        ->userAgent(config('services.sec.user_agent'))
        ->bodyHtml();

    $this->saveFilingsFromHtml($html);
  }

  public function fetch(): string {

    try{
      //Try tier 1
      return $this->fetchFromAtom();
    } catch (\Exception $e){
      \Log::warning("Sec Tier 1 failed: " . $e->getMessage());

      try{
        // Try tier 2
        $this->fetchViaScraping();
        return "Successfully saved via scraping.";
      } catch (\Exception $e) {
        \Log::warning("Sec Tier 2 failed: " . $e->getMessage());
    
        $this->fetchViaBrowser();
        return "Successfully saved via Browser.";
        }
    }
  }

}