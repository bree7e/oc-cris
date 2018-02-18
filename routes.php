<?php

Route::group(['prefix' => 'api/v1'], function () {
    Route::resource('publications', 'Bree7e\Cris\Api\Publications');
});

Route::get('api/hello', function() {
    return 'Hello World';
});

// Route::get('/welcome', 'authorName\pluginName\WelcomeController@index');


// ahmadfatoni code
// Route::post('fatoni/generate/api', array('as' => 'fatoni.generate.api', 'uses' => 'AhmadFatoni\ApiGenerator\Controllers\ApiGeneratorController@generateApi'));
// Route::post('fatoni/update/api/{id}', array('as' => 'fatoni.update.api', 'uses' => 'AhmadFatoni\ApiGenerator\Controllers\ApiGeneratorController@updateApi'));
// Route::get('fatoni/delete/api/{id}', array('as' => 'fatoni.delete.api', 'uses' => 'AhmadFatoni\ApiGenerator\Controllers\ApiGeneratorController@deleteApi'));

// Route::resource('api/publications', 'AhmadFatoni\ApiGenerator\Controllers\API\PublicationController', ['except' => ['destroy', 'create', 'edit']]);
// Route::get('api/publications/{id}/delete', ['as' => 'api/publications.delete', 'uses' => 'AhmadFatoni\ApiGenerator\Controllers\API\PublicationController@destroy']);

// Route::get('api/populate', function(){
//     $faker = Faker\Factory::create();
//     for($i = 0; $i < 20; $i++){
//         Todo::create([
//             'title' => $faker->sentence($nbWords = 6, $variableNbWords = true),
//             'description' => $faker->text($maxNbChars = 200),
//             'status' => $faker->boolean($chanceOfGettingTrue = 50),
//             'created_at' => $faker->date($format = 'Y-m-d H:i:s', $max = 'now')
//         ]);
//     }
//     return "Todos Created!";
// });
// Route::get('api/todos', function() {
//     $todos = Todo::all();
//     return $todos;
// });
// Route::post('api/add-todo', function(Request $req){
//     $data = $req->input();
//     Todo::create([
//         'title' => $data['title'],
//         'description' => $data['description'],
//         'status' => $data['status']
//     ]);
// });
// Route::post('api/delete-todo', function(Request $req){
//     $data = $req->input();
//     Todo::destroy($data['id']);
// });
// Route::post('api/toggle-todo', function(Request $req){
//     $data = $req->input();
//     Todo::where('id', $data['id'])->update(['status' => $data['status']]);
// });
// Route::post('api/update-todo', function(Request $req){
//     $data = $req->input();
//     Todo::where('id', $data['id'])
//     ->update([
//         'status' => $data['status'],
//         'title' => $data['title'],
//         'description' => $data['description']
//     ]);
// });
