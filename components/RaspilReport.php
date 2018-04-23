<?php namespace Bree7e\Cris\Components;

use Bree7e\Cris\Models\Department;
use Bree7e\Cris\Models\Publication;
use Bree7e\Cris\Models\Author;
use Cms\Classes\ComponentBase;
use Flash;
use Illuminate\Http\Request;
use October\Rain\Database\Collection;
use ApplicationException;

class RaspilReport extends ComponentBase
{
    public $authors;
    public $year;
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
        $request = Request()->all();
        $count = 0;

        switch ($request['raspil-type']) {
            case 'all': // все
                $count = $p->authors_count ?? 0;
                break;
            case 'we': // только наши
                switch ($request['except-authors']) {
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
        $request = Request()->all();
        $this->year = $request['year'];
        if (empty($this->year)) {
            throw new ApplicationException("Следует указать год");
        }

        $publications = Publication::whereReportYear($this->year)
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
            
            if ($pCountAuthor == 0) {
                continue;
            } else {
                try {
                    $pk = $p->k / $pCountAuthor;
                } catch (\DivisionByZeroError $er) {
                    throw new AjaxException("Деление на 0 авторов. Публикация $p->id");
                } catch (\Exception $ex) {
                    if (Request::ajax()) {
                        throw $ex;
                    } else {
                        Flash::error($ex->getMessage());
                    }
                    
                }
            }

            if ($request['pub-list'] === 'list') {
                $p->pk = $pk;
                $p->countAuthor = $pCountAuthor;
            }

            foreach ($p->publication_authors as $a) {

                if (!$authors->contains('id', $a->id)) {
                    $a->artWosTotal = 0;
                    $a->artScopusTotal = 0;
                    $a->artRiscTotal = 0;
                    $a->procWosTotal = 0;
                    $a->procScopusTotal = 0;
                    $a->procRiscTotal = 0;
                    $a->patentTotal = 0;
                    $a->bookTotal = 0;
                    // список всех премируемых авторов, 
                    // включая тех кто не работает в научных подразделениях
                    $authors[$a->id] = $a; 
                }

                if ($a->is_except and $request['raspil-type'] == 'we' and $request['except-authors'] == 'except') {
                    continue;
                }

                switch ($p->publication_type_id) {
                    case '1': // articles
                        if ($p->is_wos) {
                            $authors[$a->id]->artWosTotal += $pk;
                        } elseif ($p->is_scopus) {
                            $authors[$a->id]->artScopusTotal += $pk;
                        } elseif ($p->is_risc) {
                            $authors[$a->id]->artRiscTotal += $pk;
                        }
                        break;
                    case '2': // inproceedings
                        if ($p->is_wos) {
                            $authors[$a->id]->procWosTotal += $pk;
                        } elseif ($p->is_scopus) {
                            $authors[$a->id]->procScopusTotal += $pk;
                        } elseif ($p->is_risc) {
                            $authors[$a->id]->procRiscTotal += $pk;
                        }
                        break;
                    case '3': // patents
                        $authors[$a->id]->patentTotal += $pk;
                        break;
                    case '4': // books
                        $authors[$a->id]->bookTotal += $pk;
                        break;
                }

            } // $p->publication_authors

        } // foreach publications

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
