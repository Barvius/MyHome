# Api For Home Automation (RPI)

Методы

** Получение метео-данных **
> ?tooken=%tooken&dev=%dev&data=%data

%data может принимать

```
all - получение данных за весь период в json
day - получение данных за день
now - последняя строка из бд
 ```
** Работа с освещением **
> ?tooken=%tooken&lamp=%lamp&lamp_act=%lamp_act

%lamp может принимать номер лампы

%lamp_act может принимать 
```
on - включение
off - выключение
status - текущее состояние лампы в json
log - вывод данных включения/выключения в json
```
