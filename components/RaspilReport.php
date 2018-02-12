<?php namespace Bree7e\Cris\Components;

use Bree7e\Cris\Models\Department;
use Bree7e\Cris\Models\Publication;
use Bree7e\Cris\Models\Author;
use Cms\Classes\ComponentBase;
use Flash;
use Illuminate\Http\Request;
use October\Rain\Database\Collection;

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

        $publications = Publication::whereReportYear($this->year)
            ->with('publication_authors')
            ->get()
            ->keyBy('id');

        $authors = Author::ofScientificDepartments()->get()->keyBy('id');
        foreach ($publications as $p) {
            $k = 0; // общий коэффициент статьи
            switch ($p->publication_type_id) {
                case '1': // articles
                    if ($p->is_wos) {
                        $k = $request['art-wos'];
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
            $p->k = $k;
        }

        foreach ($publications as $p) {

            // если у переводной статьи коэффициент выше
            if ($p->translated_version) {
                if ($publications[$p->translated_version->id]->k >= $p->k) {
                    $p->k = 0;
                    $p->hasTranslatedVersion = true;
                }     
            }
            $k = $p->k;

            // надо проверить статьи, где есть сслыки на переводы
            // если коэффициент перевода выше, то этой поставить 0
            // и флаг есть_перевод 

            if ($k === 0) {
                continue;
            }

            // допустим k = 0,7
            $pCountAuthor = 0;
            switch ($request['raspil-type']) {
                case 'all': // все
                    $pCountAuthor = $p->authors_count;
                    break;
                case 'we': // только наши
                    switch ($request['except-authors']) {
                        case 'all': // учитывать всех
                            $pCountAuthor = $p->publication_authors->count();
                            break;
                        case 'except': // не учитвать отказавшихся
                            $pCountAuthor = 0;
                            foreach ($p->publication_authors as $author) { 
                                if ($author->is_except) {
                                    $p->hasExceptAuthors = true;
                                    continue; 
                                } else {
                                    $pCountAuthor++;
                                }
                            }
                            break;
                    }
                    break;
            }

            if ($pCountAuthor == 0) {
                $k = 0;
                continue;
            } else {
                try {
                    $pk = $k / $pCountAuthor;
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
                $p->k = $k;
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
                $a->patentTotal = 0;
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
