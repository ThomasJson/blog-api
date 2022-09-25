<?php class ArticleController extends DatabaseController {
    public function affectDataToRow(&$row, $sub_rows)
    {
        if (isset($sub_rows['appuser'])) {
            $appusers = array_filter($sub_rows['appuser'], function ($item) use ($row) {
                return $item->Id_appUser == $row->Id_appUser;
            });
            $row->appUser = count($appusers) == 1 ? array_shift($appusers) : null;
        }


        if (isset($sub_rows['theme'])) {
            $themes = array_filter($sub_rows['theme'], function ($item) use ($row) {
                return $item->Id_theme == $row->Id_theme;
            });
            $row->theme = count($themes) == 1 ? array_shift($themes) : null;
        }
    }
}?>