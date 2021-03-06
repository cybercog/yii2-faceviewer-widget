<?php

namespace nepster\faceviewer;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;


/**
 * Class Widget
 * @package nepster\faceviewer
 * 
 * Виджет показывает лицевое изображение пользователя (например аватар)
 * 
 * 
 */
class Widget extends \yii\base\Widget
{

    /**
     * Шаблон отображения представления пользователя
     * @var string/function
     */
    public $template = '{face}';

    /**
     * Сделать содержимое шаблона ссылкой
     * @var array
     */
    public $templateUrl = null;

    /**
     * Опции ссылки шаблона 
     * @var array
     */
    public $templateUrlOptions = [];

    /**
     * Идентификатор Пользователя
     * @var int
     */
    public $userId;

    /**
     * ActiveRecord Модель Пользователя
     */
    public $userModel = 'common\modules\users\models\User';

    /**
     * Атрибуты модели, которые будут доступны в виджите
     */
    public $userModelAttributes = ['username', 'name', 'surname', 'sex', 'avatar_url', 'photo_url'];

    /**
     * Опции изображения
     * @var array
     */
    public $faceImgOptions = [];

    /**
     * Url адрес изображения
     * @var string
     */
    public $faceUrl = null;

    /**
     * Директория изображений
     * @var string
     */
    public $facePath = null;

    /**
     * Url адрес изображений
     * @var string
     */
    public $faceUrlDefault = null;

    /**
     * Url дефолтного изображения
     * @var string
     */
    public $faceDefault = 'face.png';
    
    /**
     * Название свойства, Пол пользователя
     * @var string
     */
    public $faceSexField = 'sex';

    /**
     * Дефолтные изображения на основе пола пользователя
     * @var array
     */
    public $faceSexDefaultAvatar = [1 => 'male.png', 2 => 'female.png'];

    /**
     * Свойство указывающее на изображение
     * @var string
     */
    public $faceField = 'avatar_url';

    /**
     * Данные пользователя
     * @var array
     */
    public $data = [];


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->userId) {
            $userModel = new $this->userModel;
            if ($userModel instanceof ActiveRecord) {
                if ($user = $userModel::findOne($this->userId)) {
                    $this->data = $user->getAttributes($this->userModelAttributes);
                }
            }
            else {
                throw new InvalidParamException('userModel must be instanceof ActiveRecord!');
            }
        }
    }


    /**
     * @inheritdoc
     */
    public function run()
    {
        if (is_callable($this->template)) {
            return call_user_func_array($this->template, ['data' => $this->addDefaultParam($this->data)]);
        }
        else {
            if ($this->templateUrl) {
                return Html::a($this->replaceData($this->template), $this->replaceData($this->templateUrl), $this->templateUrlOptions);
            }
            return $this->replaceData($this->template);
        }
    }


    /**
     * Добавляет дефолтные параметры к массиву данных если вызвана калбек функция
     * @var array $data 
     * @resurn array
     */
    private function addDefaultParam($data)
    {
        $data = is_array($data) ? $data : [];
        
        $face = isset($this->data[$this->faceField]) ? $this->data[$this->faceField] : null;
        $default = [];
        $default['_default'] = [
            'face'                 => $this->getFace($face),
            'templateUrlOptions'   => $this->templateUrlOptions,
            'userId'               => $this->userId,
            'userModel'            => $this->userModel,
            'userModelAttributes'  => $this->userModelAttributes,            
            'faceImgOptions'       => $this->faceImgOptions,
            'faceUrl'              => $this->faceUrl,
            'facePath'             => $this->facePath,
            'faceDefault'          => $this->faceDefault,
            'faceUrlDefault'       => $this->faceUrlDefault,
            'faceSexField'         => $this->faceSexField,
            'faceSexDefaultAvatar' => $this->faceSexDefaultAvatar,
            'faceField'            => $this->faceField,
        ];
        
        return array_merge($data, $default);
    }


    /**
     * Заменяем необходимые данные
     * @var mixsed $data
     * @resurn string
     */
    private function replaceData($data)
    {
        if (is_string($data)) {
            $data = $this->replaceString($data);
        }
        else {
            if (is_array($data)) {
                foreach ($data as &$item) {
                    if (is_string($item)) {
                        $item = $this->replaceString($item);
                    }
                }
            }
        }
        return $data;
    }


    /**
     * Заменяем необходимые данные
     * @var string $string
     * @resurn string
     */
    private function replaceString($string)
    {
        preg_match_all('|{(.*)}|isU', $string, $_vars, PREG_SET_ORDER);

        $vars = [];
        foreach ($_vars as &$_var) {
            if ($_var && isset($_var[1])) {
                $vars[] = $_var[1];
            }
        }

        $newString = $string;

        foreach ($vars as &$var) {
            if ($var == 'face') {
                $newString = str_replace('{' . $var . '}', $this->getFace($this->data[$this->faceField]), $newString);
            }
            else {
                $newString = str_replace('{' . $var . '}', $this->data[$var], $newString);
            }
        }
        
        return $newString;
    }


    /**
     * Получить лицевое изображение пользователя
     * @var string $face 
     * @resurn $string
     */
    private function getFace($face)
    {
        if ($face) {
            $faceUrl = $this->faceUrl . '/' . $face;
        }
        else {
            if (isset($this->data[$this->faceSexField])) {
                if (is_array($this->faceSexDefaultAvatar) && isset($this->faceSexDefaultAvatar[$this->data[$this->faceSexField]])) {
                    $faceUrl = $this->faceUrlDefault . '/' . $this->faceSexDefaultAvatar[$this->data[$this->faceSexField]];
                }
            }
            else {
                $faceUrl = $this->faceUrlDefault . '/' . $this->faceDefault;
            }
        }

        return Html::img($faceUrl, $this->faceImgOptions);
    }

}
