<?php class TagDetailController {
    public function __construct($params)
    {
        $id = array_shift($params);
        $this->action = null;
        if(isset($id) && !ctype_digit($id)){
            return $this;
        }
        if($_SERVER['REQUEST_METHOD'] == "GET" && isset($id)) {
            $this->action = $this->getData($id);
        }
    }
    public function getData($id) {
        require_once 'tag.controller.php';
        $tagCtrl = new TagController([$id]);
        $row = $tagCtrl->getOneWith($id, ["image"]);

        require_once 'article.controller.php';
        $articleCtrl = new ArticleController([]);
        $articles = $articleCtrl->getAllWith(["article"]);

        foreach($row->articles_list as &$article){
            $filtered_articles = array_values(array_filter($articles,
            function($item) use ($article){
                return $item->Id_article == $article->Id_article;
            }));
            $article = count($filtered_articles) == 1 ? array_pop($filtered_articles) : null;
        }
        return $row;
    }
}
