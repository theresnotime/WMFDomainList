<?php
/**
 * WMFDomainList
 * 
 * PHP version 8
 *
 * @category Class
 * @package  WMFDomainList
 * @author   Sam <sam@theresnotime.co.uk>
 * @license  <https://opensource.org/licenses/MIT> MIT
 * @link     #
 */
declare(strict_types=1);
require_once __DIR__ . '/../mediawiki-config/multiversion/MWWikiversions.php';
require_once __DIR__ . '/../mediawiki-config/multiversion/MWMultiVersion.php';

/**
 * WMFDomainList
 *
 * @category Class
 * @package  WMFDomainList
 * @author   Sam <sam@theresnotime.co.uk>
 * @license  <https://opensource.org/licenses/MIT> MIT
 * @link     #
 */
class WMFDomainList
{
    // phpcs:ignore Generic.Files.LineLength.TooLong
    public string $domainRegex = "/(wikipedia|wiktionary|wikiquote|wikibooks|wikinews|wikisource|wikiversity|wikimedia|wikivoyage)/";
    public array $exceptions = [
        "metawiki" => "meta.wikimedia.org",
        "mediawikiwiki" => "www.mediawiki.org",
        "testwikidatawiki" => "test.wikidata.org",
        "commonswiki" => "commons.wikimedia.org",
        "etwiki" => "et.wikipedia.org", // ?
        "foundationwiki" => "foundation.wikimedia.org",
        "incubatorwiki" => "incubator.wikimedia.org",
        "loginwiki" => "login.wikimedia.org",
        "outreachwiki" => "outreach.wikimedia.org",
        "specieswiki" => "species.wikimedia.org",
        "votewiki" => "vote.wikimedia.org",
        "wikidatawiki" => "www.wikidata.org",
        "wikimaniawiki" => "wikimania.wikimedia.org"
    ];
    public array $ignore = [
        "apiportalwiki",
        "labswiki",
        "labtestwiki",
        "sourceswiki",
    ];
    protected bool $verbose;
    protected bool $check;
    public array $all;
    public array $public;
    public array $domains = [];

    /**
     * WMFDomainList constructor.
     *
     * @param bool $verbose Run in verbose mode
     * @param bool $check   Check if the domain is valid
     */
    public function __construct(bool $verbose = false, bool $check = true)
    {
        $this->verbose = $verbose;
        $this->check = $check;
        $this->all = ( new MWWikiversions() )->getAllDbListsForCLI();
        $open = $this->all['open'];
        $private = $this->all['private'];
        $this->public = array_diff($open, $private);
    }

    /**
     * Generate the list of domains
     * 
     * @return array
     */
    public function generate(): array
    {
        foreach ( $this->public as $wiki ) {
            if (in_array($wiki, $this->ignore) ) {
                continue;
            }

            $wiki = $this->normalise($wiki);
            $url = "https://$wiki/w/api.php";

            if ($this->check ) {
                if ($this->ping($url) ) {
                    if ($this->verbose ) {
                        echo "[VERIFY PASS]: " . $url . PHP_EOL;
                    }
                    array_push($this->domains, $wiki);
                } else {
                    echo "[VERIFY FAIL]: " . $url . PHP_EOL;
                }
            } else {
                array_push($this->domains, $wiki);
            }
        }
        return $this->domains;
    }

    /**
     * Write domains to JSON file
     * 
     * @param string $out Output file
     * 
     * @return string
     */
    public function writeJSON(string $out = 'domains.json'): string
    {
        $json = json_encode(
            [
                'meta' => [
                    'version' => '1.0.0',
                    'source' => 'https://github.com/theresnotime/WMFDomainList'
                ],
                'generated' => date('Y-m-d H:i:s'),
                'domains' => $this->generate()
            ],
            JSON_PRETTY_PRINT
        );
        file_put_contents($out, $json);
        return $json;
    }
    
    /**
     * Ping a URL to check if it's valid
     * 
     * @param string $url URL to ping
     * 
     * @return bool
     */
    protected function ping(string $url): bool
    {
        stream_context_set_default(
            array(
                'http' => array(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    "header"  => "User-agent: WMFDomainList Generation (https://w.wiki/69K7)",
                )
            )
        );
        $headers = get_headers($url);
        if ($headers ) {
            $httpCode = intval(substr($headers[0], 9, 3));
            return $httpCode >= 200 && $httpCode < 300;
        } else {
            return false;
        }
    }
    
    /**
     * Normalise a wiki domain
     * 
     * @param string $wiki Wiki domain
     * 
     * @return string
     */
    protected function normalise(string $wiki): string
    {
        if (array_key_exists($wiki, $this->exceptions) ) {
            return $this->exceptions[ $wiki ];
        }
    
        $wiki = preg_replace('/_/', '-', $wiki);
        $wiki = preg_replace('/wiki$/', 'wikipedia', $wiki);
        $wiki = preg_replace($this->domainRegex, '.$1.org', $wiki);
        return $wiki;
    }
}