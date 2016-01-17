# yii-messagemedia
Implementation of MessageMedia SMS API using Yii framework (http://www.messagemedia.com/sms-api/soap)

## Usage
- clone repository (```git clone git@github.com:dorubratu/messagemedia.git```)
- run ```composer up``` to get dependencies (Yii / MessageMedia-PHP)
- update your MessageMedia username / password in ```protected/config/settings.php```
- run ```./yiic messagemediatest``` from protected directory

### Examples
- ```./yiic messagemediatest sendMessage --to=+40720123456 --content=Hello!```