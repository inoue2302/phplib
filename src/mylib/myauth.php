<?php
namespace Mylib;
/**
* 
*/
class myAuth 
{
    private $_redirect_url = null;

    public function __construct($redirect)
    {
        $this->_redirect_url = $redirect;
    }

    public function redirect()
    {
        Response::redirect($this->_redirect_url);
    }
    public function check_login()
    {
        //既にログイン済みであればブログトップページにリダイレクト
        Auth::check() and $this->redirect();
    }

    public function do_login($username,$password)
    {
        $error = false;
       if($username && $password) {
            $auth = Auth::instance();

            //認証
            if ($auth->login($username,$password)) {
                //ブログトップにリダイレクト
                Response::redirect('article');
            } else {
                $error = true;
            }

            if( $auth->login($username,$password) ) {
                $this->redirect();
            }
            return $error;
        }
    }

    public function do_logout()
    {
        //ログアウト
        $auth = Auth::instance();
        $auth->logout();

        $this->redirect();
    }
}
