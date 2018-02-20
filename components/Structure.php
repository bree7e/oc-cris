<?php namespace Bree7e\Cris\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Bree7e\Cris\Models\Author;
use Bree7e\Cris\Models\Department;
use Bree7e\Cris\Models\Publication;

class Structure extends ComponentBase
{
    public $departments;

    /**
     * @var string Ссылка на страницу об авторе
     */    
    public $profilePageUrl;    

    public function componentDetails()
    {
        return [
            'name'        => 'Structure',
            'description' => 'Структурный состав сотрудников института'
        ];
    }

    public function defineProperties()
    {
        return [
            'profilePage' => [
                'title' => 'Profile Page',
                'description' => 'Страница используемая для вывода отдельной публикации',
                'type' => 'dropdown',
                'default' => 'publications',
            ]          
        ];
    }

    public function getProfilePageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }    

    public function onRun()
    {
        $this->getDepartments();
        $profilePage = $this->property('profilePage');        
    }

    public function getDepartments() 
    {
        $departments = Department::siblings()->with('authors')->with('positions')->get();

        // высчитать сумму веса сотрудника по его должностям в поле positionSum
        // нужно сделать это рекурсивно
        // foreach ($departments as $department) {
        //     $department->authors->each(function ($item, $key) {
        //         $item->positionSum = $item->positions->pluck('sort_order')->sum();
        //     });
        //     $b = $department->authors;
        // }

        // foreach ($departments as $department) {
        //     $department->authors = $department->authors->sortBy('positionSum');
        // }
        $this->departments = $departments;
    }
  
}