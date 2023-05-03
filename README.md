# SOFT LINE Geo DB
Conjunto de datos actualizado en **formato base de datos MySQL** de la organización territorial española (comunidades, provincias, municipios, comarcas y otras entidades de población), incluyendo códigos postales e información geográfica, con tablas optimizadas para consultas rápidas, mínimo espacio posible de los registros relacionados y uso fácil.

![Obtener provincia y municipio por codigo postal](/images/softlinegeodb-resolver.gif) ![Muestra mapa político de España](/images/softlinegeodb-spain-demomap-peq.png)

## TLDR;

Para los impacientes:

* Descargar el [archivo 7z](https://github.com/softline-informatica/softlinegeodb/blob/main/softlinegeodb-spain-minimal-db.sql.7z) (comprimido con [7-zip](https://www.7-zip.org/)) que contiene los volcados SQL (_dumps_) de las tablas mínimas. Las tablas adicionales se publicarán en una próxima versión.
* Echa un vistazo a [las tablas](https://github.com/softline-informatica/softlinegeodb#tablas) y definición de IDs.
* Hay municipios con código postal único, municipios que tienen varios códigos postales y también existen municipios que comparten un mismo código postal.
* Probado en MariaDB 10.11.2, 10.1, 10.5 y 10.6 aunque por la simplicidad de las tablas debería ser compatible con versiones anteriores y MySQL 5 y 8.
* Usado en aplicaciones reales utilizadas diariamente con entradas de datos dependientes de códigos postales, provincias, municipios...

## Demos

En la [página de Demos](https://github.com/softline-informatica/softlinegeodb/blob/main/DEMOS.md) encontrarás algunas de las cosas interesantes que podemos hacer con **Soft Line Geo DB**

## Los datos

Países|250|
|:---|:---|

|Municipios España|8.131|Códigos postales|11.053|
|:---|:---|:---|:---|

La organización territorial de España se inicia con 19 demarcaciones autónomas, cada una dividida en una o varias provincias (52). Cada provincia se divide a su vez en varios municipios (en total 8.131).​ Los municipios son las entidades territoriales básicas en esta organización, aunque existen otras entidades territoriales como agrupaciones de municipios o entidades de rango inferior al municipio, conocidas como "entidades locales menores".

|Autonomías|19|
|:---|:---|
||17 comunidades autónomas de las que 2 son archipiélagos: las islas Baleares y las Canarias<br />2 ciudades autónomas en África: Ceuta y Melilla|

|Provincias|52|Islas|11|
|:---|:---|:---|:---|
||49 sin islas<br />3 con islas<br />&nbsp;|<br /><br /><br />|0<br />4 islas Baleares (1 provincia)<br />7 islas Canarias (en 2 provincias)|

## Motivación
Para poder tomar decisiones basadas en datos, estos deben tener cierta consistencia y ser lo más precisos posible. Cuando hablamos de datos relacionados con la ubicación, resulta asombrosa la cantidad de programas informáticos y servicios que recogen esta información de la peor manera posible en términos de tratamiento y analítica de datos (abominaciones como números inventados para los municipios o directamente el nombre de la "población" o municipio en cada registro, incluso a veces, texto libre, en vez de identificadores).

Con el objetivo de intentar mejorar esta mala situación del software y registros en general, publicamos “Soft Line Geo DB”, para que quien quiera “hacer las cosas bien” tenga recursos a su alcance.

Estas son las motivaciones principales que nos llevaron a crear esta base de datos:

1. Disponer de una base de datos relacional en la que estén normalizados los datos de provincia y municipios españoles, usando IDs numéricos _compatibles_ con los oficiales y no números inventados con AUTOINCREMENT a salto de mata, que es lo fácil.
2. Poder determinar a qué municipio oficial pertenece un código postal y cuántas calles comprende. La problemática de los códigos postales es que estos no son estrictamente una subdivisión de los municipios: hay municipios con un solo código postal, otros que tienen varios, y también hay municipios que comparten un mismo código postal.
3. Geoposicionar (con latitud/longitud) los municipios y tener otra información, como la isla a la que pertenece, provincia, comunidad autónoma, nº de habitantes, altitud...
4. Poder presentarle al usuario los municipios de la ISLA a la que se refiere, en lugar de "como hace todo el mundo": darle los municipios de todas las islas mezclados, solo porque son "de la misma provincia"...
5. Poder relacionar datos del INE y otras fuentes -geográficas, estadísticas- a partir de referencias consistentes y compatibles (ID municipio, ID provincia, a veces se tiene solo el código postal como dato fiable, etc.)

## Actualización de los datos
Actualización enero 2023. Los datos procesados están creados a partir de fuentes oficiales: INE y CNIG (IGN), tan pronto el INE publicó en Internet los datos de enero de 2023 del Callejero de Censo Electoral, exactamente el día 9 de marzo de 2023. El procesado de datos se realizó el 26 de marzo, descargando también entonces los últimos datos del CNIG. Ver más información en el siguiente apartado [fuentes de datos](https://github.com/softline-informatica/softlinegeodb#fuentes-de-datos).

Esta base de datos procesados se construye desde 2016 y ha sido actualizada en las siguientes fechas, si bien se publica en Github el 1 de mayo de 2023:
Fecha|Comentario|
|:---|:---|
|19 de marzo de 2023|Actualización con datos 2023|
|1 de enero de 2021|Actualización con datos 2020|
|15 de junio de 2019|Actualización con datos 2019|
|27 de mayo de 2018|Actualización con datos 2018, 1ª versión con tabla entidades_geo|
|20 de agosto de 2017|Actualización con datos 2017, 1ª versión con tabla de países|
|2 de mayo de 2016|1ª versión de las tablas con datos 2016 del INE y CNIG/IGN|

## Fuentes de datos
**INE** (Instituto Nacional de Estadística) que mantiene los datos de municipios / provincias / comunidades autónomas y el callejero del censo electoral con sus códigos postales.

- Recurso: [INE: Relación de municipios y sus códigos por provincias.](https://www.ine.es/dyngs/INEbase/es/operacion.htm?c=Estadistica_C&cid=1254736177031&menu=ultiDatos&idp=1254734710990) Últimos datos
- Recurso: [Cartografía secciones censales y callejero de Censo Electoral](https://www.ine.es/ss/Satellite?L=es_ES&c=Page&cid=1259952026632&p=1259952026632&pagename=ProductosYServicios%2FPYSLayout) - (Ver fichero nacional del "Callejero de Censo Electoral")

**CNIG** (Centro Nacional de Información Geográfica) dirigido por el **IGN** (Instituto Geográfico Nacional), que mantiene diversos conjuntos de datos geográficos.

- Recursos: En el [Centro de Descargas del CNIG](http://centrodedescargas.cnig.es/CentroDescargas/index.jsp) ver el _"Nomenclátor Geográfico Básico de España"_ y el _"Nomenclátor Geográfico de Municipios y Entidades de Población"_ 

### Contraste de los datos
En mayo de 2023 se contrastó la fiabilidad de los datos de códigos postales: la tabla "municipios_cp" de este proyecto -que relaciona los códigos postales con los municipios- se chequeó contra los datos CSV generados por [este otro proyecto similar de Íñigo Flores](https://github.com/inigoflores/ds-codigos-postales-ine-es). La comparación fue exacta para los datos de 2023-1 (Enero).

## LAS TABLAS

La versión de datos _mínima_ consta de 7 tablas SQL.

![Tablas versión minimalista de softlinegeodb](/images/softlinegeodb_tables2023.png)

### Definición de los IDs en las tablas

|ID|Nombre|Descripción|
|---|---|---|
|id_ccaa|Comunidad Autónoma|Número del 1 al 19, sin cero inicial, basta 1 byte y por eso el tipo de dato es TINYINT UNSIGNED|
|id_provincia|Provincia|Número del 1 al 52, sin cero inicial, basta 1 byte y por eso el tipo de dato es TINYINT UNSIGNED|
|id_municipio|Municipio|Para poder identificar un municipio por un número, y almacenarlo como un tipo númerico, sin ceros iniciales (al contrario que el INE), lo que hacemos es prefijar el "id_ccaa" (Comunidad Autónoma) y seguir el patrón del INE: dos cifras del 01 al 52 para la provincia, y 3 cifras para el código de municipio, del 001 al 999. De esta manera nos bastan 3 bytes para referenciar un municipio de forma única (tipo MEDIUMINT UNSIGNED) y además podemos obtener la Comunidad Autónoma leyendo hacia atrás el ID municipio.<br /> **Por ejemplo** el código municipio de PALMA DE MALLORCA en el INE sería **07040** (provincia 07, municipio 040). En nuestra base de datos se queda como **407040** ya que el código 4 es la comunidad autónoma de las Islas Baleares.<br />La ciudad de Valencia, cuyo código de municipio INE es **46250** (46 código provincia, 250 código de municipio), se queda como **1046250** ya que 10 es el ID de Comunidad Autónoma.

La versión de datos extra incluye datos procesados del callejero del INE y las entidades poblacionales adicionales e información geográfica extra del CNIG, todo asociado a los ID de municipio, pero su uso es más raro y ocupan mucho más espacio (son cientos de miles de filas).

![Tablas extra de la versión full de softlinegeodb](/images/softlinegeodb_tables-extra2023.png)

### softlinegeodb_ine_ccaa
Las 19 autonomías (por unificar: CCAA o Comunidades Autónomas) con las ciudades autónomas de Ceuta y Melilla.

![Estructura de la tabla "ine_ccaa" (Comunidades autónomas españolas)](/images/softlinegeodb_ine_ccaa-struct.png)
![Datos de la tabla "ine_ccaa"](/images/softlinegeodb_ine_ccaa-data.png)

### softlinegeodb_ine_provincias
Cada provincia pertenece a una Comunidad Autónoma ("ccaa") y se indica el municipio que es la capital de la provincia. Existe además un nombre, nombre corto y nombre alternativo.

![Estructura de la tabla "ine_provincias" (Provincias españolas)](/images/softlinegeodb_ine_provincias-struct.png)

### softlinegeodb_ine_islas
Permite asociar las 11 islas de entidad en España con su provincia, pues las islas Canarias se agrupan en 2 provincias.

![Estructura de la tabla "ine_islas" (Islas españolas)](/images/softlinegeodb_ine_islas-struct.png)

### softlinegeodb_ine_municipios
Cada municipio tiene el ID de su Comunidad Autónoma ("ccaa") y Provincia "id_provincia". El ID de isla será distinto de 0 si procede.

![Estructura de la tabla "ine_municipios" (Municipios de España)](/images/softlinegeodb_ine_municipios-struct.png)

### softlinegeodb_ine_municipios_cp
De esta tabla podemos extraer a qué municipio(s) pertenece un código postal. Algunos códigos postales están compartidos entre 2 municipios o más y un municipio puede tener uno o varios códigos postales.

![Estructura de la tabla "ine_municipios_cp" (Códigos postales a municipios españoles)](/images/softlinegeodb_ine_municipios_cp-struct.png)

### softlinegeodb_ine_municipios_geo
De aquí obtendremos información geográfica de un ID municipio dado, incluyendo latitud/longitud del centroide del municipio, nº de habitantes, etc.

![Estructura de la tabla "ine_municipios_geo" (Información geográfica de los Municipios españoles)](/images/softlinegeodb_ine_municipios_geo-struct.png)

## En proyecto
Asociación de todos los núcleos de población del CNIG con el padrón municipal, para asociar nº de habitantes y posición geográfica de todos los "CUN" y no solo municipios.

# Referencias y Línea de tiempo

La base de datos de códigos postales oficial de Correos siempre ha sido de pago, y desde hace un tiempo ofrece varias versiones. Los precios van desde 800 € a 5.000 €<br />
- 2013: [Correos: Base de datos (de pago) de códigos postales](https://www.correos.es/es/es/empresas/marketing/identifica-a-tus-clientes-potenciales/base-de-datos-de-codigos-postales)

El siguiente artículo de _Pablo Barrachina_ es el que motivó la creación de "softlinegeodb" en 2016:<br />
- 2013: [Relacionar el Código INE con su Código Postal](http://switchoffandletsgo.blogspot.com/2013/04/relacionar-el-codigo-ine-con-su-codigo.html) de **_Pablo Barrachina_**

- 2016: Primera versión (interna) de "Soft Line Geo DB", de **_Juan Gabriel Covas_** para SOFT LINE Informática

- 2017: [Relación de códigos postales de España](https://github.com/inigoflores/ds-codigos-postales) de **_Iñigo Flores_** junto el código INE del municipio al que pertenecen, con las geometrías asociadas a cada código postal en formato .geojson

- 2017: [Base de Datos de Códigos Postales](https://postal.cat/index.es.html) de **_Javier Casares_**, volcado en CSV, Excel y SQL, pero sin fuentes contrastables. Indica: "La información de este sitio recoge datos de Correos, Google Maps, Códigos Postales y otras fuentes. La información puede no ser exacta." - Actualizado en 2019.

- 2019: [Petición formal (42 solicitantes) a “Datos abiertos” del Gobierno (datos.gob.es) de una “Base de delimitaciones de los CODIGOS POSTALES DE ESPAÑA”](https://datos.gob.es/es/peticiones-datos/base-de-delimitaciones-de-los-codigos-postales-de-espana). Estado: “No viable” - La base de delimitaciones de códigos postales actualmente está sujeta a comercialización.

- 2022: [Dataset que proporciona un listado de todos los códigos postales de España asociados a los municipios y unidades poblacionales](https://github.com/inigoflores/ds-codigos-postales-ine-es) de **_Íñigo Flores_**, usa como fuente el Callejero del Censo Electoral (del INE).

El siguiente es un fantástico trabajo de _Francisco Goerlich_ y es casi un tratado en códigos postales. Para los interesados en obtener cartografía actualizada de los códigos postales en España:<br />
- 2022: [Elaboración de un mapa de Códigos Postales de España con recursos libres](https://www.uv.es/goerlich/Ivie/CodPost.html) de **_Francisco Goerlich_ (Universidad de Valencia e Ivie)**

2023: [Publicación de "Soft Line Geo DB" en GitHub](https://github.com/softline-informatica/softlinegeodb)

<br /><br />

### Créditos y licencia

"Soft Line GeoDB" es un trabajo de _Juan Gabriel Covas_ para **SOFT LINE Informática**. 2016 - 2023<br />
Basado en el artículo de 2013: [Relacionar el Código INE con su Código Postal](http://switchoffandletsgo.blogspot.com/2013/04/relacionar-el-codigo-ine-con-su-codigo.html) de _Pablo Barrachina_

[Licencia GPL-3.0](https://github.com/softline-informatica/softlinegeodb/blob/main/LICENSE)<br />
Uso libre de "Softlinegeodb". Las modificaciones, si se distribuyen, deben hacerse con código fuente.<br />
