# Api For Home Automation (RPI)

Методы

### Получение метео-данных
> ?tooken=%tooken&dev=%dev&data=%data

%data может принимать

```
all - получение данных за весь период в json
day - получение данных за день
now - последняя строка из бд
 ```
### Работа с освещением 
> ?tooken=%tooken&lamp=%lamp&lamp_act=%lamp_act

%lamp может принимать номер лампы
%lamp_act может принимать 
```
on - включение
off - выключение
status - текущее состояние лампы в json
log - вывод данных включения/выключения в json
```

### Работа с системой отопления
> ?tooken=%tooken&heating_system=%heating_system

%heating_system может принимать
```
get_mode - Получение текущего режима работы системы
set_mode - Установка режима + GET value=[0,1]
get_max_temp - Получение верхнего предела температуры
get_min_yemp - Получение нижнего предела температуры
set_max_temp - Установка верхнего предела температуры + GET value=[0-9]
set_min_temp - Установка нижнего предела температуры + GET value=[0-9]
```

управление насосом
> ?tooken=%tooken&heating_system=pump&pump_act=%pump_act
%pump_act может принимать 
```
on - включение
off - выключение
status - текущее состояние насоса в json
log - вывод данных включения/выключения в json
```
