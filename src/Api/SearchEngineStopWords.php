<?php

namespace Sunnysideup\SearchSimpleSmart\Api;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

class SearchEngineStopWords
{
    use Extensible;
    use Injectable;
    use Configurable;

    private static $list_short = [
        'I',
        'a',
        'about',
        'an',
        'are',
        'as',
        'at',
        'be',
        'by',
        'for',
        'from',
        'how',
        'in',
        'is',
        'it',
        'of',
        'on',
        'or',
        'that',
        'the',
        'this',
        'to',
        'was',
        'what',
        'when',
        'where',
        'who',
        'will',
        'with',
    ];

    private static $list_medium = [
        'above',
        'after',
        'again',
        'against',
        'all',
        'am',
        'and',
        'any',
        "aren't",
        'because',
        'been',
        'before',
        'being',
        'below',
        'between',
        'both',
        'but',
        "can't",
        'cannot',
        'could',
        "couldn't",
        'did',
        "didn't",
        'do',
        'does',
        "doesn't",
        'doing',
        "don't",
        'down',
        'during',
        'each',
        'few',
        'further',
        'had',
        "hadn't",
        'has',
        "hasn't",
        'have',
        "haven't",
        'having',
        'he',
        "he'd",
        "he'll",
        "he's",
        'her',
        'here',
        "here's",
        'hers',
        'herself',
        'him',
        'himself',
        'his',
        "how's",
        'i',
        "i'd",
        "i'll",
        "i'm",
        "i've",
        'if',
        'into',
        "isn't",
        "it's",
        'its',
        'itself',
        "let's",
        'me',
        'more',
        'most',
        "mustn't",
        'my',
        'myself',
        'no',
        'nor',
        'not',
        'off',
        'once',
        'only',
        'other',
        'ought',
        'our',
        'ours	',
        'ourselves',
        'out',
        'over',
        'own',
        'same',
        "shan't",
        'she',
        "she'd",
        "she'll",
        "she's",
        'should',
        "shouldn't",
        'so',
        'some',
        'such',
        'than',
        "that's",
        'their',
        'theirs',
        'them',
        'themselves',
        'then',
        'there',
        "there's",
        'these',
        'they',
        "they'd",
        "they'll",
        "they're",
        "they've",
        'those',
        'through',
        'too',
        'under',
        'until',
        'up',
        'very',
        "wasn't",
        'we',
        "we'd",
        "we'll",
        "we're",
        "we've",
        'were',
        "weren't",
        "what's",
        "when's",
        "where's",
        'which',
        'while',
        "who's",
        'whom',
        'why',
        "why's",
        "won't",
        'would',
        "wouldn't",
        'you',
        "you'd",
        "you'll",
        "you're",
        "you've",
        'your',
        'yours',
        'yourself',
        'yourselves',
    ];

    private static $list_long = [
        'across',
        'afterwards',
        'almost',
        'alone',
        'along',
        'already',
        'also',
        'although',
        'always',
        'among',
        'amongst',
        'amoungst',
        'amount',
        'another',
        'anyhow',
        'anyone',
        'anything',
        'anyway',
        'anywhere',
        'around',
        'back',
        'became',
        'become',
        'becomes',
        'becoming',
        'beforehand',
        'behind',
        'beside',
        'besides',
        'beyond',
        'bill',
        'bottom',
        'call',
        'can',
        'cant',
        'co',
        'con',
        'couldnt',
        'cry',
        'de',
        'describe',
        'detail',
        'done',
        'due',
        'eg',
        'eight',
        'either',
        'eleven',
        'else',
        'elsewhere',
        'empty',
        'enough',
        'etc',
        'even',
        'ever',
        'every',
        'everyone',
        'everything',
        'everywhere',
        'except',
        'fifteen',
        'fify',
        'fill',
        'find',
        'fire',
        'first',
        'five',
        'former',
        'formerly',
        'forty',
        'found',
        'four',
        'front',
        'full',
        'get',
        'give',
        'go',
        'hasnt',
        'hence',
        'hereafter',
        'hereby',
        'herein',
        'hereupon',
        'however',
        'hundred',
        'ie',
        'inc',
        'indeed',
        'interest',
        'keep',
        'last',
        'latter',
        'latterly',
        'least',
        'less',
        'ltd',
        'made',
        'many',
        'may',
        'meanwhile',
        'might',
        'mill',
        'mine',
        'moreover',
        'mostly',
        'move',
        'much',
        'must',
        'name',
        'namely',
        'neither',
        'never',
        'nevertheless',
        'next',
        'nine',
        'nobody',
        'none',
        'noone',
        'nothing',
        'now',
        'nowhere',
        'often',
        'one',
        'onto',
        'others',
        'otherwise',
        'ours',
        'part',
        'per',
        'perhaps',
        'please',
        'put',
        'rather',
        're',
        'see',
        'seem',
        'seemed',
        'seeming',
        'seems',
        'serious',
        'several',
        'show',
        'side',
        'since',
        'sincere',
        'six',
        'sixty',
        'somehow',
        'someone',
        'something',
        'sometime',
        'sometimes',
        'somewhere',
        'still',
        'system',
        'take',
        'ten',
        'thence',
        'thereafter',
        'thereby',
        'therefore',
        'therein',
        'thereupon',
        'thickv',
        'thin',
        'third',
        'though',
        'three',
        'throughout',
        'thru',
        'thus',
        'together',
        'top',
        'toward',
        'towards',
        'twelve',
        'twenty',
        'two',
        'un',
        'upon',
        'us',
        'via',
        'well',
        'whatever',
        'whence',
        'whenever',
        'whereafter',
        'whereas',
        'whereby',
        'wherein',
        'whereupon',
        'wherever',
        'whether',
        'whither',
        'whoever',
        'whole',
        'whose',
        'within',
        'without',
        'yet',
    ];

    private static $list_extra_long = [
        'able',
        'abst',
        'accordance',
        'according',
        'accordingly',
        'act',
        'actually',
        'added',
        'adj',
        'affected',
        'affecting',
        'affects',
        'ah',
        'announce',
        'anybody',
        'anymore',
        'anyways',
        'apparently',
        'approximately',
        'aren',
        'arent',
        'arise',
        'aside',
        'ask',
        'asking',
        'auth',
        'available',
        'away',
        'awfully',
        'b',
        'begin',
        'beginning',
        'beginnings',
        'begins',
        'believe',
        'biol',
        'brief',
        'briefly',
        'c',
        'ca',
        'came',
        'cause',
        'causes',
        'certain',
        'certainly',
        'com',
        'come',
        'comes',
        'contain',
        'containing',
        'contains',
        'd',
        'date',
        'different',
        'downwards',
        'e',
        'ed',
        'edu',
        'effect',
        'eighty',
        'end',
        'ending',
        'especially',
        'et',
        'et-al',
        'everybody',
        'ex',
        'f',
        'far',
        'ff',
        'fifth',
        'fix',
        'followed',
        'following',
        'follows',
        'forth',
        'furthermore',
        'g',
        'gave',
        'gets',
        'getting',
        'given',
        'gives',
        'giving',
        'goes',
        'gone',
        'got',
        'gotten',
        'h',
        'happens',
        'hardly',
        'hed',
        'heres',
        'hes',
        'hi',
        'hid',
        'hither',
        'home',
        'howbeit',
        'id',
        'im',
        'immediate',
        'immediately',
        'importance',
        'important',
        'index',
        'information',
        'instead',
        'invention',
        'inward',
        'itd',
        "it'll",
        'j',
        'just',
        'k',
        'keep	keeps',
        'kept',
        'kg',
        'km',
        'know',
        'known',
        'knows',
        'l',
        'largely',
        'lately',
        'later',
        'lest',
        'let',
        'lets',
        'like',
        'liked',
        'likely',
        'line',
        'little',
        "'ll",
        'look',
        'looking',
        'looks',
        'm',
        'mainly',
        'make',
        'makes',
        'maybe',
        'mean',
        'means',
        'meantime',
        'merely',
        'mg',
        'million',
        'miss',
        'ml',
        'mr',
        'mrs',
        'mug',
        'n',
        'na',
        'nay',
        'nd',
        'near',
        'nearly',
        'necessarily',
        'necessary',
        'need',
        'needs',
        'new',
        'ninety',
        'non',
        'nonetheless',
        'normally',
        'nos',
        'noted',
        'o',
        'obtain',
        'obtained',
        'obviously',
        'oh',
        'ok',
        'okay',
        'old',
        'omitted',
        'ones',
        'ord',
        'outside',
        'overall',
        'owing',
        'p',
        'page',
        'pages',
        'particular',
        'particularly',
        'past',
        'placed',
        'plus',
        'poorly',
        'possible',
        'possibly',
        'potentially',
        'pp',
        'predominantly',
        'present',
        'previously',
        'primarily',
        'probably',
        'promptly',
        'proud',
        'provides',
        'q',
        'que',
        'quickly',
        'quite',
        'qv',
        'r',
        'ran',
        'rd',
        'readily',
        'really',
        'recent',
        'recently',
        'ref',
        'refs',
        'regarding',
        'regardless',
        'regards',
        'related',
        'relatively',
        'research',
        'respectively',
        'resulted',
        'resulting',
        'results',
        'right',
        'run',
        's',
        'said',
        'saw',
        'say',
        'saying',
        'says',
        'sec',
        'section',
        'seeing',
        'seen',
        'self',
        'selves',
        'sent',
        'seven',
        'shall',
        'shed',
        'shes',
        'showed',
        'shown',
        'showns',
        'shows',
        'significant',
        'significantly',
        'similar',
        'similarly',
        'slightly',
        'somebody',
        'somethan',
        'somewhat',
        'soon',
        'sorry',
        'specifically',
        'specified',
        'specify',
        'specifying',
        'stop',
        'strongly',
        'sub',
        'substantially',
        'successfully',
        'sufficiently',
        'suggest',
        'sup',
        'sure	t',
        'taken',
        'taking',
        'tell',
        'tends',
        'th',
        'thank',
        'thanks',
        'thanx',
        "that'll",
        'thats',
        "that've",
        'thered',
        "there'll",
        'thereof',
        'therere',
        'theres',
        'thereto',
        "there've",
        'theyd',
        'theyre',
        'think',
        'thou',
        'thoughh',
        'thousand',
        'throug',
        'til',
        'tip',
        'took',
        'tried',
        'tries',
        'truly',
        'try',
        'trying',
        'ts',
        'twice',
        'u',
        'unfortunately',
        'unless',
        'unlike',
        'unlikely',
        'unto',
        'ups',
        'use',
        'used',
        'useful',
        'usefully',
        'usefulness',
        'uses',
        'using',
        'usually',
        'v',
        'value',
        'various',
        "'ve",
        'viz',
        'vol',
        'vols',
        'vs',
        'w',
        'want',
        'wants',
        'wasnt',
        'way',
        'wed',
        'welcome',
        'went',
        'werent',
        "what'll",
        'whats',
        'wheres',
        'whim',
        'whod',
        "who'll",
        'whomever',
        'whos',
        'widely',
        'willing',
        'wish',
        'wont',
        'words',
        'world',
        'wouldnt',
        'www',
        'x',
        'y',
        'yes',
        'youd',
        'youre',
        'z',
        'zero',
    ];

    public static function get_list($size): array
    {
        $listNames = [
            'short',
            'medium',
            'long',
            'extra_long',
        ];
        if (! in_array($size, $listNames, true)) {
            user_error('Name should be in ' . print_r($listNames, 1));
        }

        $covered = [];
        foreach ($listNames as $name) {
            $listWords = Config::inst()->get(self::class, 'list_' . $name);
            foreach ($listWords as $word) {
                $covered[$word] = $word;
            }

            if ($name === $size) {
                sort($covered);

                break;
            }
        }

        return $covered;
    }
}
