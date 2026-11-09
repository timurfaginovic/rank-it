<?php

require_once __DIR__ . '/ServiceException.php';

class WorldBankService
{
    private const COUNTRIES = ['BIH', 'HRV', 'SRB', 'MNE', 'SVN'];
    private $transport;

    public function __construct(?callable $transport = null)
    {
        $this->transport = $transport;
    }

    private function fetch(string $url): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'RankIt-UniversityProject/1.0',
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $failed = $body === false;
        curl_close($curl);
        if ($failed) {
            throw new ServiceException('Population data is temporarily unavailable. Please try again later.', 503);
        }
        return ['status' => $status, 'body' => $body];
    }

    public function createDraft(array $data): array
    {
        if (array_keys($data) !== ['year'] || !is_int($data['year']) || $data['year'] < 1960 || $data['year'] > (int) date('Y')) {
            throw new ServiceException('Provide only year, an integer between 1960 and the current year.');
        }
        $year = $data['year'];
        $url = 'https://api.worldbank.org/v2/country/' . implode(';', self::COUNTRIES) . '/indicator/SP.POP.TOTL?date=' . $year . '&format=json&per_page=100';
        try {
            $response = $this->transport ? ($this->transport)($url) : $this->fetch($url);
            if ($response['status'] !== 200) {
                throw new ServiceException('The population provider returned an error. Manual challenge creation is still available.', 502);
            }
            $json = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($json) || !isset($json[0]['pages'], $json[1]) || (int) $json[0]['pages'] !== 1 || !is_array($json[1]) || count($json[1]) !== 5) {
                throw new ServiceException('The provider did not return five usable country records.', 502);
            }
            $countries = [];
            foreach ($json[1] as $row) {
                $code = $row['countryiso3code'] ?? '';
                $value = $row['value'] ?? null;
                $name = $row['country']['value'] ?? null;
                if (!in_array($code, self::COUNTRIES, true) || isset($countries[$code]) || (string) ($row['date'] ?? '') !== (string) $year || !is_numeric($value) || !is_finite((float) $value) || $value <= 0 || !is_string($name) || trim($name) === '' || mb_strlen($name) > 100) {
                    throw new ServiceException('Population values are missing or inconsistent for that year.', 502);
                }
                $countries[$code] = ['item_text' => $name, 'population' => (float) $value];
            }
            if (count(array_unique(array_column($countries, 'population'), SORT_REGULAR)) !== 5) {
                throw new ServiceException('Population values are tied; this draft has no unique order.', 502);
            }
            usort($countries, fn($a, $b) => $b['population'] <=> $a['population']);
            foreach ($countries as $index => &$country) {
                $country['correct_position'] = $index + 1;
            }
            unset($country);
            return [
                'title' => "Countries by population in $year",
                'description' => "Rank these five countries from most to least populated in $year. Source: World Bank, SP.POP.TOTL.",
                'status' => 'Draft',
                'year' => $year,
                'items' => $countries,
                'source' => ['name' => 'World Bank', 'indicator' => 'SP.POP.TOTL', 'url' => $url],
            ];
        } catch (Throwable $error) {
            $message = $error instanceof ServiceException ? $error->getMessage() : 'The population provider returned an invalid response.';
            error_log(gmdate('c') . " World Bank year=$year: $message\n", 3, __DIR__ . '/../logs/error.log');
            throw new ServiceException($message, $error instanceof ServiceException ? $error->status : 502);
        }
    }
}
