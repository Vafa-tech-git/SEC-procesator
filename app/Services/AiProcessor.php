<?php

namespace App\Services;

use App\Models\Filing;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class AiProcessor{

  private function getCleanedText($url): string {
    $response = Http::withHeaders([
      'User-Agent' => config('services.sec.user_agent')
    ])->get($url);
  
    $crawler = new Crawler($response->body());

    $crawler->filter('script, style, ix\\:header, link')->each(function ($node){
      $node->getNode(0)->parentNode?->removeChild($node->getNode(0));
    });

    $text = $crawler->text(null, true);
  
  return mb_substr($text, 0, 15000);
  }

  public function analyze(Filing $filing): void {
    $text = $this->getCleanedText($filing->link);

    $prompt = <<<EOD
You are an expert financial analyst and natural language processing system specialized in reading SEC filings.

Your task is to analyze the text provided below, which is an excerpt from a corporate SEC filing. You must generate a concise summary and determine the overall sentiment of the text.

**Instructions:**

1.  **Analyze the Content:** Focus on material financial information, including revenue, net income, earnings per share (EPS), guidance/outlook, significant risks, and strategic shifts. Ignore standard legal boilerplate or non-material administrative text.
2.  **Draft a Summary:** Create a clear, professional summary of the key points. The summary should be digestible for an investor reading a news feed (approx. 3-5 sentences).
3.  **Determine Sentiment:** Classify the sentiment as 'Positive', 'Negative', or 'Neutral' based on the following criteria:
    * **Positive:** Revenue/earnings growth, beat estimates, raised guidance, favorable regulatory outcomes, or strategic expansion.
    * **Negative:** Missed estimates, lowered guidance, legal probes, declining margins, or significant new risks.
    * **Neutral:** Routine disclosures, mixed results (e.g., revenue up but earnings down), or maintaining status quo without significant surprises.
4.  **Output Format:** Return **ONLY** a raw JSON object. Do not include markdown formatting (like ```json), distinct preambles, or explanations.

**JSON Schema:**
{
  "summary": "String",
  "sentiment": "String" // One of: "Positive", "Negative", "Neutral"
}

**Text to Analyze:**
$text
EOD;

    $localpayload = [
      'model' => config('services.ollama.model'),
      'prompt' => $prompt,
      'format' => 'json',
      'stream' => false,
    ];

    $cloudpayload = [
      'model' => config('services.ollama.model'),
      'messages' => [['role' => 'user', 'content' => $prompt]],
      'stream' => false,
    ];

    try{
      $response = Http::timeout(60)->post(config('services.ollama.local_url'), $localpayload);
      if (!$response->successful()) throw new \Exception("Local AI Error");
      $rawAiText = $response->json()['response'] ?? '';
    } catch (\Exception $e){
      \Log::warning("Local AI Failed: " . $e->getMessage());

      try{
        $response = Http::withToken(config('services.ollama.key'))
              ->timeout(120)
              ->post(config('services.ollama.cloud_url'), $cloudpayload);
      
        if (!$response->successful()) throw new \Exception("Cloud AI error: " . $response->status());
      
        $rawAiText = $response->json()['message']['content'] ?? '';      

      } catch (\Exception $cloudError){
        \Log::error("Cloud AI also failed:" . $cloudError->getMessage());
        return;
      }
    }

    $start = strpos($rawAiText, '{');
    $end = strrpos($rawAiText, '}');

    if ($start !== false && $end !== false){
      $jsonOnly = substr($rawAiText, $start, $end - $start + 1);
      $data = json_decode($jsonOnly, true);
  
      if ($data && isset($data['summary'])){
        $filing->update([
            'summary' => strip_tags($data['summary']),
            'sentiment' => $data['sentiment'] ?? 'Neutral',
        ]);
      }
      } else{
          \Log::error("AI Response did not contain valid JSON brackets: " . $rawAiText);
      }
  }

}