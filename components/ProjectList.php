<?php namespace Bree7e\Cris\Components;

use Cms\Classes\ComponentBase;
use Bree7e\Cris\Models\Project;
use October\Rain\Database\Collection;
use Cms\Classes\Page;

class ProjectList extends ComponentBase
{
    /**
     * @var October\Rain\Database\Collection Список проектов, когда не задан :id
     */
    public $projects;
    
    /**
     * Reference to the page for render project.
     * @var string
     */    
    public $projectPage;

    public function componentDetails()
    {
        return [
            'name' => 'Список проектов',
            'description' => 'Отображение списка выполняемых преоктов, а также завершенных.',
        ];
    }

    public function defineProperties()
    {
        return [
            'projectPage' => [
                'title' => 'Страница проекта',
                'description' => 'Страница используемая для вывода отдельной публикации',
                'type' => 'dropdown',
                'default' => 'projects',
            ]          
        ];
    }

    public function getProjectPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }    

    public function onRun()
    {
        $this->projects = Project::all();
        $this->projectPage = $this->property('projectPage');        
    }

}
