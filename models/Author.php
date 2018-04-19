<?php namespace Bree7e\Cris\Models;

use Bree7e\Cris\Models\Department;
use AuthorAlternativeName as Synonym;
use October\Rain\Auth\Models\User as UserModel;
use October\Rain\Database\Collection;

// TODO поискать как в октябре делают слаги

function ru2Lat($string, $gost = false)
{
    if ($gost) {
        $replace = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b", "В" => "V", "в" => "v", "Г" => "G", "г" => "g", "Д" => "D", "д" => "d",
            "Е" => "E", "е" => "e", "Ё" => "E", "ё" => "e", "Ж" => "Zh", "ж" => "zh", "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
            "Й" => "I", "й" => "i", "К" => "K", "к" => "k", "Л" => "L", "л" => "l", "М" => "M", "м" => "m", "Н" => "N", "н" => "n", "О" => "O", "о" => "o",
            "П" => "P", "п" => "p", "Р" => "R", "р" => "r", "С" => "S", "с" => "s", "Т" => "T", "т" => "t", "У" => "U", "у" => "u", "Ф" => "F", "ф" => "f",
            "Х" => "Kh", "х" => "kh", "Ц" => "Tc", "ц" => "tc", "Ч" => "Ch", "ч" => "ch", "Ш" => "Sh", "ш" => "sh", "Щ" => "Shch", "щ" => "shch",
            "Ы" => "Y", "ы" => "y", "Э" => "E", "э" => "e", "Ю" => "Iu", "ю" => "iu", "Я" => "Ia", "я" => "ia", "ъ" => "", "ь" => "");
    } else {
        $arStrES = array("ае", "уе", "ое", "ые", "ие", "эе", "яе", "юе", "ёе", "ее", "ье", "ъе", "ый", "ий");
        $arStrOS = array("аё", "уё", "оё", "ыё", "иё", "эё", "яё", "юё", "ёё", "её", "ьё", "ъё", "ый", "ий");
        $arStrRS = array("а$", "у$", "о$", "ы$", "и$", "э$", "я$", "ю$", "ё$", "е$", "ь$", "ъ$", "@", "@");

        $replace = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b", "В" => "V", "в" => "v", "Г" => "G", "г" => "g", "Д" => "D", "д" => "d",
            "Е" => "Ye", "е" => "e", "Ё" => "Ye", "ё" => "e", "Ж" => "Zh", "ж" => "zh", "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
            "Й" => "Y", "й" => "y", "К" => "K", "к" => "k", "Л" => "L", "л" => "l", "М" => "M", "м" => "m", "Н" => "N", "н" => "n",
            "О" => "O", "о" => "o", "П" => "P", "п" => "p", "Р" => "R", "р" => "r", "С" => "S", "с" => "s", "Т" => "T", "т" => "t",
            "У" => "U", "у" => "u", "Ф" => "F", "ф" => "f", "Х" => "Kh", "х" => "kh", "Ц" => "Ts", "ц" => "ts", "Ч" => "Ch", "ч" => "ch",
            "Ш" => "Sh", "ш" => "sh", "Щ" => "Sh", "щ" => "sh", "Ъ" => "", "ъ" => "", "Ы" => "Y", "ы" => "y", "Ь" => "", "ь" => "",
            "Э" => "E", "э" => "e", "Ю" => "Yu", "ю" => "yu", "Я" => "Ya", "я" => "ya", "@" => "y", "$" => "ye");

        $string = str_replace($arStrES, $arStrRS, $string);
        $string = str_replace($arStrOS, $arStrRS, $string);
    }

    return iconv("UTF-8", "UTF-8//IGNORE", strtr($string, $replace));
}

/**
 * Author Model
 */
class Author extends UserModel
{
    public $hasMany = [
        'projects' => [
            'Bree7e\Cris\Models\Project',
            'key' => 'rb_user_id', // Ключ этой модели в таблице 1-М
        ],
        'synonyms' => [
            'Bree7e\Cris\Models\AuthorAlternativeName',
            'key' => 'rb_author_id',
        ],
    ];

    public $attachOne = [
        'avatar' => ['System\Models\File'],
    ];


    public function __construct() {
        /**
         * The attributes that should be mutated to dates.
         *
         * @var array
         */
        $this->dates = array_merge($this->dates,  [
            'birthdate',
            'asp_start',
            'asp_finish'
        ]);
        /**
         * @var array List of attribute names which are json encoded and decoded from the database.
         */
        $this->jsonable = array_merge($this->jsonable, ['phones']);

        $this->belongsToMany = array_merge($this->belongsToMany, [
            'publications' => [
                'Bree7e\Cris\Models\Publication',
                'table' => 'bree7e_cris_authors_publications', // таблица многие-ко-многим
                'key' => 'rb_author_id', // Ключ этой модели в таблице М-М
                'otherKey' => 'publication_id',
            ],
            'positions' => [
                'Bree7e\Cris\Models\Position',
                'table' => 'bree7e_cris_authors_departments_positions',
                'key' => 'rb_author_id',
                'otherKey' => 'position_id',
                'pivot' => 'department_id',
                'pivotModel' => 'Bree7e\Cris\Models\AuthorPositionPivot',
            ],
            'departments' => [
                'Bree7e\Cris\Models\Department',
                'table' => 'bree7e_cris_authors_departments_positions',
                'key' => 'rb_author_id',
                'otherKey' => 'department_id',
            ],
    
        ]);      
        
        $this->belongsTo = array_merge($this->belongsTo, [
            'adviser' => [
                'Bree7e\Cris\Models\Author',
                'key' => 'rb_adviser_id',
            ],
            'consultant' => [
                'Bree7e\Cris\Models\Author',
                'key' => 'rb_consultant_id',
            ],
        ]);    
        
        parent::__construct();
    
    }

    /**
     * Фильтрация авторов определенного подразделения
     *
     * @param Query $query
     * @param Array $departmentIds
     * @return Query
     */
    public function scopeOfDepartments($query, $departmentIds)
    {
        return $query->whereHas('departments', function ($q) use ($departmentIds) {
            $q->whereIn('department_id', $departmentIds);
        });
    }

    /**
     * Фильтрация авторов научных подразделений
     */
    public function scopeOfScientificDepartments($query)
    {
        $departmentIds = Department::select('id')->isScientific()->get()->toArray();
        return $query->ofDepartments($departmentIds);
    }

    /**
     * Cоздание 2 основных синонимов автора
     *
     * @param int $id Идентификатор автора
     * @return void
     */
    public function generateSynonyms()
    {
        // main russian synonym
        $synonym = new Synonym();
        $synonym->lastname = $this->surname;
        $synonym->firstname = mb_substr($this->name, 0, 1, "UTF-8") . ".";
        $synonym->middlename = mb_substr($this->middlename, 0, 1, "UTF-8") . ".";
        $this->synonyms()->save($synonym);

        // main english synonym
        $synonym = new Synonym();
        // $synonym->author()->associate($this); // $synonym->rb_author_id = $this->getKey();
        $synonym->lastname = ru2Lat($this->surname);
        $synonym->firstname = mb_substr(ru2Lat($this->name), 0, 1, "UTF-8") . ".";
        $synonym->middlename = mb_substr(ru2Lat($this->middlename), 0, 1, "UTF-8") . ".";
        $this->synonyms()->save($synonym);

    }

    public static function generateAllSynonyms()
    {
        $authors = Author::all(); // почитать про глобальную область видмости self::all() или static::all()

        foreach ($authors as $author) {
            if ($author->synonyms->count() == 0) {
                $author->generateSynonyms();
            }
        }
    }

    /**
     * Возвращает коллекцию подобранных авторов
     *
     * @param string $fullName - строка вида "Иванов П.В."
     * @return \October\Rain\Database\Collection 
     */
    public static function getSuggestions(string $fullName): Collection
    {
        $trimFullName = preg_replace('/\s+/', '', $fullName);
        $result = new Collection();
        $authors = self::with('synonyms')->get();
        foreach ($authors as $a) {
            foreach ($a->synonyms as $s) {
                $halfSynonym = $s->lastname.$s->firstname;
                $fullSynonym = $halfSynonym.$s->middlename;
                if ($trimFullName === $halfSynonym || $trimFullName === $fullSynonym) {
                    $result->push($a);
                    break;
                }
            }
        }
        return $result;
    }
}
