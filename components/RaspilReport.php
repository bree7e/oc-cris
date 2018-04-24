<?php namespace Bree7e\Cris\Components;

use Bree7e\Cris\Models\Department;
use Bree7e\Cris\Models\Publication;
use Bree7e\Cris\Models\Author;
use Cms\Classes\ComponentBase;
use Flash;
use Illuminate\Http\Request;
use October\Rain\Database\Collection;
use ApplicationException;
use AjaxException;

class RaspilReport extends ComponentBase
{
    public $authors;
    public $departments;
    public $publications;

    public function componentDetails()
    {
        return [
            'name' => 'CRIS Report',
            'description' => 'Отчет для службы учёного секретариата',
        ];
    }

    /**
     * Выбор коэффициента публикации
     * @var Bree7e\Cris\Models\Publication $p - публикация
     *
     * @return float Коэффициент
     */
    protected function chooseCoefficient(Publication $p): float
    {   
        $request = Request()->all();
        $request = input();
        $k = 0; 

        switch ($p->publication_type_id) {
            case '1': // articles
                if ($p->is_wos) {
                    switch ($p->quartile) {
                        case 'Q1':
                            $k = $request['art-wos-q1'];
                        break;
                        case 'Q2':
                            $k = $request['art-wos-q2'];
                        break;
                        case 'Q3':
                            $k = $request['art-wos-q3'];
                        break;
                        case 'Q4':
                            $k = $request['art-wos-q4'];
                        break;
                        default:
                            $k = $request['art-wos-q5'];
                        break;
                    }
                } elseif ($p->is_scopus) {
                    $k = $request['art-scopus'];
                } elseif ($p->is_risc) {
                    $k = $request['art-risc'];
                }
                break;

            case '2': // inproceedings
                if ($p->is_wos) {
                    $k = $request['proc-wos'];
                } elseif ($p->is_scopus) {
                    $k = $request['proc-scopus'];
                } elseif ($p->is_risc) {
                    $k = $request['proc-risc'];

                }
                break;

            case '3': // patents
                $k = $request['patents'];
                break;

            case '4': // books
                $k = $request['books'];
                break;
        } 
        return $k;       
    }

    protected function getPublicationAuthorCount(Publication $p): int
    {
        $count = 0;
        $reportType = input('raspil-type');
        $except = input('except-authors');

        switch ($reportType) {
            case 'all': // все
                $count = $p->authors_count ?? 0;
                break;
            case 'we': // только наши
                switch ($except) {
                    case 'all': // учитывать всех
                        $count = $p->publication_authors->count();
                        break;
                    case 'except': // не учитвать отказавшихся
                        $count = 0;
                        foreach ($p->publication_authors as $author) { 
                            if ($author->is_except) {
                                continue; 
                            } else {
                                $count++;
                            }
                        }
                        break;
                }
                break;
        }
        return $count;
    }

    protected function initAuthorSumsByPublicationTypes(Author $author): Author
    {
        $author->artWosQ1Total = 0;
        $author->artWosQ2Total = 0;
        $author->artWosQ3Total = 0;
        $author->artWosQ4Total = 0;
        $author->artWosQ5Total = 0;
        $author->artWosTotal = 0;
        $author->artScopusTotal = 0;
        $author->artRiscTotal = 0;
        $author->procWosTotal = 0;
        $author->procScopusTotal = 0;
        $author->procRiscTotal = 0;
        $author->patentTotal = 0;
        $author->bookTotal = 0;
        return $author;
    }

    protected function addDividedPublicationCoefficientToAuthor(Publication $publication, Author $author): Author
    {
        switch ($publication->publication_type_id) {
            case '1': // articles
                if ($publication->is_wos) {
                    // TODO Нужна логика по отдельным квартилям
                    $author->artWosTotal += $publication->dividedK;
                } elseif ($publication->is_scopus) {
                    $author->artScopusTotal += $publication->dividedK;
                } elseif ($publication->is_risc) {
                    $author->artRiscTotal += $publication->dividedK;
                }
                break;
            case '2': // inproceedings
                if ($publication->is_wos) {
                    $author->procWosTotal += $publication->dividedK;
                } elseif ($publication->is_scopus) {
                    $author->procScopusTotal += $publication->dividedK;
                } elseif ($publication->is_risc) {
                    $author->procRiscTotal += $publication->dividedK;
                }
                break;
            case '3': // patents
                $author->patentTotal += $publication->dividedK;
                break;
            case '4': // books
                $author->bookTotal += $publication->dividedK;
                break;
        }

        return $author;
    }
    /**
     * Расчет ПРНД
     * @var $k - общий коэффициент статьи
     * @var $pk - персональный коэффициент автора за статью
     * @var $authors[] - список всех премируемых авторов
     *
     * @return void
     */
    protected function onLoadReport()
    {
        $request = input();

        $startYear = input('year[from]');
        $finishYear = input('year[to]');
        if (empty($startYear)) {
            throw new ApplicationException('Следует указать "Первый отчетный год"');
        }
        if (empty($finishYear)) {
            throw new ApplicationException('Следует указать "Последний отчетный год"');
        }

        $publications = Publication::whereReportYearBetween($startYear, $finishYear)
            ->with('publication_authors')
            ->get()
            ->keyBy('id');
 
        foreach ($publications as $p) {
            $p->k = $this->chooseCoefficient($p);
        }

        // отдельный цикл, чтобы сравнить коэффициенты переводных версий
        foreach ($publications as $p) {
            if ($p->translated_version) {
                if ($publications[$p->translated_version->id]->k >= $p->k) {
                    $p->k = 0;
                    $p->hasTranslatedVersion = true;
                    continue;
                }     
            }
                        
            // пометить публикации с отказавшимися авторами
            if (($request['raspil-type'] == 'we') and ($request['except-authors'] == 'except')){
                foreach ($p->publication_authors as $author) { 
                    if ($author->is_except) {
                        $p->hasExceptAuthors = true;
                        break; 
                    }
                }
            }
        }        
        
        $authors = Author::ofScientificDepartments()->get()->keyBy('id');

        foreach ($publications as $p) {              
            $pCountAuthor = $this->getPublicationAuthorCount($p);            
            if ($pCountAuthor === 0) continue;
            $p->countAuthor = $pCountAuthor;

            try {
                $pk = $p->k / $pCountAuthor;
                $p->dividedK = $pk;
            } catch (\DivisionByZeroError $er) {
                throw new ApplicationException("Деление на 0 авторов. Публикация $p->id");
            } 

            foreach ($p->publication_authors as $a) {
                if (!$authors->contains('id', $a->id)) {
                    $a = $this->initAuthorSumsByPublicationTypes($a);
                    $authors[$a->id] = $a; 
                }

                if ($a->is_except and $request['raspil-type'] == 'we' and $request['except-authors'] == 'except') {
                    continue;
                }

                $authors[$a->id] = $this->addDividedPublicationCoefficientToAuthor($p, $authors[$a->id]);
            }
        }

        if ($request['pub-list'] === 'list') {
            $this->publications = $publications->sortBy(function($row) {
                return sprintf('%-12s%s', $row->k, $row->authors);
            });
        }

        foreach ($authors as $a) {
            if ($a->is_except and $request['raspil-type'] == 'we' and $request['except-authors'] == 'except') {
                $a->hasExcepted = true; // исключён из расчета
                $a->total = 0;
                continue;
            }            
            $a->total =
                $a->artWosTotal +
                $a->artScopusTotal +
                $a->artRiscTotal +
                $a->procWosTotal +
                $a->procScopusTotal +
                $a->procRiscTotal +
                $a->bookTotal +
                $a->patentTotal;
        }

        // перечень научных отделов
        $departments = Department::isScientific()->get();
        $nonScientific = new Department();
        $nonScientific->id = -1;
        $nonScientific->name = 'Без научного отдела';
        $nonScientific->is_scientific = false;
        
        $departments->push($nonScientific);
        $departments = $departments->keyBy('id');
        $departments = $departments->each(function ($d, $key) {
            $d->workers = new Collection();
        });

        // группировка по отделам
        foreach ($authors as $a) {
            $added = false;
            foreach ($a->departments as $d) {
                if ($d->is_scientific) { // если отдел попадает в научные
                    $departments[$d->id]->workers->push($a);
                } else {
                    $departments[-1]->workers->push($a);
                }
            }
        }

        // сортировка по фамилии внутри отделов
        foreach ($departments as $department) {
            $department->workers = $department->workers->sortby('surname');
        }

        $this->departments = $departments;
    }
}
