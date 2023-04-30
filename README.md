# softlinegeodb
Conjunto de datos actualizado de los municipios españoles, códigos postales e información geográfica, en formato base de datos MySQL con tablas optimizadas. Creado a partir de fuentes oficiales: INE y CNIG (IGN)

## Datos

|Autonomías >|Provincias >|Islas >|Municipios >|Códigos postales|
|:---:|:---:|:---:|:---:|:---:|
|19|52|11|8.131|11.053|
|17 comunidades||4 Baleares|||
|2 ciudad autónoma||3 Las Palmas|||
|||4 Sta. Cruz de Tenerife|||

## Motivación
1. Disponer de una base de datos relacional en la que estén normalizados los datos de provincia y municipios españoles, usando IDs numéricos _compatibles_ con los oficiales y no números inventados con AUTOINCREMENT que es lo fácil.
2. Poder determinar a qué municipio oficial pertenece un código postal y cuántas calles comprende (aunque hay códigos postales que pertenecen a más de un municipio).
3. Obtener la geoposición (latitud/longitud) de los municipios y otra información, como isla, provincia, comunidad autónoma, nº de habitantes, altitud...
4. Poder presentarle al usuario los municipios de la ISLA a la que se refiere, en lugar de como hace "todo el mundo": darle los municipios de todas las islas mezclados, solo porque son "de la misma provincia"...

## Fuentes de datos
INE - Instituto Nacional de Estadística, que mantiene los datos de municipios / provincias / comunidades autónomas y el callejero del censo electoral con sus códigos postales.

CNIG - Centro Nacional de Información Geográfica, dirigido por el IGN (Instituto Geográfico Nacional), que mantiene diversos conjuntos de datos geográficos.

## Tablas
### softlinegeodb_ine_ccaa
Las 19 comunidades autónomas (con las ciudades autónomas de Ceuta y Melilla).

![Estructura de la tabla "ine_ccaa" (Comunidades autónomas)](/images/softlinegeodb_ine_ccaa-struct.png)
![Datos de la tabla "ine_ccaa"](/images/softlinegeodb_ine_ccaa-data.png)
