<?php


namespace App\Http\ViewComposers;


use App\Category;
use Illuminate\Contracts\View\View;

class CategoriesAllComposer
{
    public $categories;
    public function __construct(Category $categories)
    {
        $this->categories = $categories;
    }
    public function compose(View $view) {
        $view->with('categories', $this->categories->all());
    }
}
