# yii-messagemedia
Implementation of MessageMedia SMS API (http://www.messagemedia.com/sms-api/soap) using Yii 1.1 PHP framework (http://www.yiiframework.com). 
Main class (```MessageMedia.php```) can be used on any PHP framework with some changes.

## Usage
- clone repository (```git clone git@github.com:dorubratu/yii-messagemedia.git```)
- run ```composer up``` to get dependencies (Yii / MessageMedia-PHP)
- update your MessageMedia username / password in ```protected/config/settings.php```
- run ```./yiic messagemediatest``` from ```protected``` directory

### TestCommand Examples
- ```./yiic messagemediatest sendMessage --to=+40720123456 --content=Hello!```
- ```./yiic messagemediatest blockNumber --number=+40720123456```
- ```./yiic messagemediatest checkReplies```

### WebApplication Usage
- define MessageMedia as component in webapp configuration file (see ```config/console.php```) 
- Example usage: ```Yii::app()->messageMedia->sendMessage($to, $message);```