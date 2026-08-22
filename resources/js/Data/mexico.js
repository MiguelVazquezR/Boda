// Estados y ciudades de la República Mexicana.
// Cada estado contiene una lista de sus principales ciudades / municipios.
// La ciudad se filtra automáticamente al seleccionar el estado.

export const estadosMexico = [
    {
        estado: 'Aguascalientes',
        ciudades: ['Aguascalientes', 'Jesús María', 'San Francisco de los Romo', 'Calvillo', 'Rincón de Romos', 'Pabellón de Arteaga', 'Asientos', 'Cosío', 'El Llano', 'San José de Gracia', 'Tepezalá'],
    },
    {
        estado: 'Baja California',
        ciudades: ['Mexicali', 'Tijuana', 'Ensenada', 'Playas de Rosarito', 'Tecate', 'San Quintín', 'San Felipe', 'Los Cabos'],
    },
    {
        estado: 'Baja California Sur',
        ciudades: ['La Paz', 'Cabo San Lucas', 'San José del Cabo', 'Ciudad Constitución', 'Loreto', 'Santa Rosalía', 'Guerrero Negro', 'Todos Santos'],
    },
    {
        estado: 'Campeche',
        ciudades: ['Campeche', 'Ciudad del Carmen', 'Champotón', 'Escárcega', 'Calkiní', 'Hopelchén', 'Tenabo', 'Hecelchakán', 'Palizada', 'Calakmul', 'Candelaria', 'Seybaplaya', 'Dzitbalché'],
    },
    {
        estado: 'Chiapas',
        ciudades: ['Tuxtla Gutiérrez', 'Tapachula', 'San Cristóbal de las Casas', 'Comitán de Domínguez', 'Palenque', 'Tonalá', 'Ocosingo', 'Chiapa de Corzo', 'Arriaga', 'Huixtla', 'Cintalapa', 'Villaflores', 'Las Margaritas', 'Pichucalco', 'Salto de Agua', 'Yajalón', 'Motozintla', 'Berriozábal', 'Jiquipilas', 'Acapetahua', 'Mapastepec', 'Pijijiapan', 'Reforma', 'Suchiapa'],
    },
    {
        estado: 'Chihuahua',
        ciudades: ['Chihuahua', 'Ciudad Juárez', 'Delicias', 'Cuauhtémoc', 'Hidalgo del Parral', 'Nuevo Casas Grandes', 'Camargo', 'Jiménez', 'Ojinaga', 'Meoqui', 'Saucillo', 'Creel', 'Madera', 'Casas Grandes', 'Guerrero', 'Aldama', 'Ascensión', 'Guadalupe y Calvo', 'Batopilas', 'Bocoyna', 'Guachochi'],
    },
    {
        estado: 'Ciudad de México',
        ciudades: ['Álvaro Obregón', 'Azcapotzalco', 'Benito Juárez', 'Coyoacán', 'Cuajimalpa de Morelos', 'Cuauhtémoc', 'Gustavo A. Madero', 'Iztacalco', 'Iztapalapa', 'La Magdalena Contreras', 'Miguel Hidalgo', 'Milpa Alta', 'Tláhuac', 'Tlalpan', 'Venustiano Carranza', 'Xochimilco'],
    },
    {
        estado: 'Coahuila',
        ciudades: ['Saltillo', 'Torreón', 'Monclova', 'Piedras Negras', 'Ciudad Acuña', 'Frontera', 'Sabinas', 'Nueva Rosita', 'San Pedro de las Colonias', 'Ramos Arizpe', 'Matamoros', 'Parras de la Fuente', 'Arteaga', 'Allende', 'Nava', 'Castaños', 'Cuatro Ciénegas', 'Viesca', 'Ocampo', 'Múzquiz'],
    },
    {
        estado: 'Colima',
        ciudades: ['Colima', 'Manzanillo', 'Tecomán', 'Villa de Álvarez', 'Comala', 'Coquimatlán', 'Cuauhtémoc', 'Armería', 'Ixtlahuacán', 'Minatitlán'],
    },
    {
        estado: 'Durango',
        ciudades: ['Victoria de Durango', 'Gómez Palacio', 'Lerdo', 'Santiago Papasquiaro', 'Guadalupe Victoria', 'El Salto', 'Canatlán', 'Pueblo Nuevo', 'Cuencamé', 'Nazas', 'Rodeo', 'Tepehuanes', 'Mapimí', 'Mezquital', 'Nombre de Dios', 'Tamazula', 'Vicente Guerrero', 'Santa María del Oro'],
    },
    {
        estado: 'Estado de México',
        ciudades: ['Toluca de Lerdo', 'Ecatepec de Morelos', 'Ciudad Nezahualcóyotl', 'Naucalpan de Juárez', 'Tlalnepantla de Baz', 'Chimalhuacán', 'Cuautitlán Izcalli', 'Atizapán de Zaragoza', 'Ixtapaluca', 'Nicolás Romero', 'Tecámac', 'Chalco', 'Valle de Chalco Solidaridad', 'Coacalco de Berriozábal', 'Los Reyes Acaquilpan', 'Huixquilucan', 'Texcoco de Mora', 'Metepec', 'Zumpango', 'Lerma de Villada', 'Amecameca de Juárez', 'Tenango del Valle', 'San Mateo Atenco', 'Ocoyoacac', 'Jilotepec de Abasolo', 'Tejupilco de Hidalgo', 'Valle de Bravo', 'Ixtapan de la Sal', 'Atlacomulco', 'El Oro de Hidalgo', 'Tenancingo', 'Capulhuac', 'San Martín de las Pirámides', 'Villa del Carbón'],
    },
    {
        estado: 'Guanajuato',
        ciudades: ['Guanajuato', 'León de los Aldama', 'Irapuato', 'Celaya', 'Salamanca', 'San Miguel de Allende', 'Silao de la Victoria', 'Dolores Hidalgo Cuna de la Independencia Nacional', 'San Luis de la Paz', 'Pénjamo', 'Valle de Santiago', 'Acámbaro', 'Salvatierra', 'San Felipe', 'Moroleón', 'Uriangato', 'Apaseo el Grande', 'Apaseo el Alto', 'Santa Cruz de Juventino Rosas', 'Villagrán', 'Cortázar', 'Comonfort', 'San José Iturbide', 'Yuriria', 'Abasolo', 'Cuerámaro', 'Manuel Doblado', 'Purísima del Rincón', 'Romita', 'San Francisco del Rincón', 'Ocampo', 'San Diego de la Unión', 'Tarimoro', 'Huanímaro'],
    },
    {
        estado: 'Guerrero',
        ciudades: ['Chilpancingo de los Bravo', 'Acapulco de Juárez', 'Iguala de la Independencia', 'Taxco de Alarcón', 'Zihuatanejo', 'Coyuca de Catalán', 'Tixtla de Guerrero', 'Chilapa de Álvarez', 'Atoyac de Álvarez', 'Tecpan de Galeana', 'Petatlán', 'San Marcos', 'Ayutla de los Libres', 'Ometepec', 'Tlapa de Comonfort', 'Teloloapan', 'Arcelia', 'Ciudad Altamirano', 'Cruz Grande', 'Marquelia', 'Zumpango del Río', 'Huitzuco de los Figueroa', 'Quechultenango', 'Tierra Colorada', 'Coyuca de Benítez', 'San Jerónimo de Juárez', 'Cuajinicuilapa', 'La Unión', 'Apaxtla de Castrejón'],
    },
    {
        estado: 'Hidalgo',
        ciudades: ['Pachuca de Soto', 'Tula de Allende', 'Tizayuca', 'Huejutla de Reyes', 'Ixmiquilpan', 'Actopan', 'Tepeji del Río de Ocampo', 'Tulancingo de Bravo', 'Apan', 'Tezontepec de Aldama', 'Zacualtipán de Ángeles', 'Zimapán', 'Progreso de Obregón', 'Mixquiahuala de Juárez', 'Santiago Tulantepec', 'Cuautepec de Hinojosa', 'Mineral de la Reforma', 'Ciudad Sahagún', 'Huichapan', 'Cardonal', 'Jacala de Ledezma', 'Tenango de Doria', 'Atotonilco el Grande', 'Mineral del Chico', 'Mineral del Monte', 'Emiliano Zapata', 'San Agustín Tlaxiaca', 'Francisco I. Madero'],
    },
    {
        estado: 'Jalisco',
        ciudades: ['Guadalajara', 'Zapopan', 'San Pedro Tlaquepaque', 'Tonalá', 'Tlajomulco de Zúñiga', 'Puerto Vallarta', 'Lagos de Moreno', 'Tepatitlán de Morelos', 'Ciudad Guzmán', 'Ocotlán', 'Chapala', 'El Salto', 'Zapotlanejo', 'Atotonilco el Alto', 'Arandas', 'San Juan de los Lagos', 'Encarnación de Díaz', 'Tequila', 'Ameca', 'Autlán de Navarro', 'Colotlán', 'Sayula', 'Tamazula de Gordiano', 'Tuxpan', 'La Barca', 'Poncitlán', 'Jocotepec', 'Tala', 'Ixtlahuacán de los Membrillos', 'Acatic', 'Jalostotitlán', 'San Miguel el Alto', 'Yahualica de González Gallo', 'Teocaltiche', 'Cocula', 'Zacoalco de Torres', 'El Grullo', 'Cihuatlán', 'La Huerta', 'Tomatlán', 'Mascota', 'Talpa de Allende', 'San Sebastián del Oeste', 'Casimiro Castillo', 'Cuquío', 'Gómez Farías'],
    },
    {
        estado: 'Michoacán',
        ciudades: ['Morelia', 'Uruapan', 'Zamora de Hidalgo', 'Ciudad Lázaro Cárdenas', 'Apatzingán de la Constitución', 'Zitácuaro', 'Pátzcuaro', 'Ciudad Hidalgo', 'La Piedad de Cabadas', 'Maravatío de Ocampo', 'Jacona de Plancarte', 'Tangancícuaro de Arista', 'Puruándiro', 'Zacapu', 'Sahuayo de Morelos', 'Los Reyes de Salgado', 'Cotija de la Paz', 'Chilchota', 'Paracho de Verduzco', 'Tacámbaro de Codallos', 'Yurécuaro', 'Jiquilpan de Juárez', 'Tepalcatepec', 'Buenavista Tomatlán', 'Coalcomán de Vázquez Pallares', 'Aguililla', 'Huetamo de Núñez', 'Huandacareo', 'Cuitzeo del Porvenir', 'Tarímbaro', 'Charo', 'Zinapécuaro', 'Álvaro Obregón', 'Ario de Rosales', 'Quiroga', 'Erongarícuaro', 'Tzintzuntzan', 'Vista Hermosa'],
    },
    {
        estado: 'Morelos',
        ciudades: ['Cuernavaca', 'Jiutepec', 'Cuautla', 'Temixco', 'Yautepec de Zaragoza', 'Emiliano Zapata', 'Xochitepec', 'Jojutla', 'Puente de Ixtla', 'Tepoztlán', 'Tlayacapan', 'Zacatepec de Hidalgo', 'Ayala', 'Tlaltizapán de Zapata', 'Mazatepec', 'Miacatlán', 'Coatlán del Río', 'Tetecala', 'Amacuzac', 'Huitzilac', 'Tepalcingo', 'Jonacatepec', 'Axochiapan', 'Totolapan', 'Atlatlahucan', 'Yecapixtla', 'Ocuituco', 'Tetela del Volcán', 'Zacualpan de Amilpas', 'Temoac'],
    },
    {
        estado: 'Nayarit',
        ciudades: ['Tepic', 'Xalisco', 'Bahía de Banderas', 'Compostela', 'San Blas', 'Santiago Ixcuintla', 'Tecuala', 'Acaponeta', 'Rosamorada', 'Ruiz', 'Tuxpan', 'Ixtlán del Río', 'Jala', 'Ahuacatlán', 'Amatlán de Cañas', 'El Nayar', 'La Yesca', 'Huajicori', 'San Pedro Lagunillas'],
    },
    {
        estado: 'Nuevo León',
        ciudades: ['Monterrey', 'Guadalupe', 'San Nicolás de los Garza', 'Apodaca', 'General Escobedo', 'Santa Catarina', 'San Pedro Garza García', 'García', 'Ciudad Benito Juárez', 'Cadereyta Jiménez', 'Linares', 'Montemorelos', 'Sabinas Hidalgo', 'Galeana', 'China', 'General Terán', 'Allende', 'Santiago', 'Ciénega de Flores', 'Salinas Victoria', 'El Carmen', 'Hidalgo', 'Bustamante', 'Anáhuac', 'Lampazos de Naranjo', 'Dr. Arroyo', 'Aramberri', 'Iturbide', 'Pesquería', 'Marín', 'Agualeguas', 'Cerralvo', 'General Bravo', 'General Treviño', 'Melchor Ocampo', 'Los Ramones'],
    },
    {
        estado: 'Oaxaca',
        ciudades: ['Oaxaca de Juárez', 'San Juan Bautista Tuxtepec', 'Salina Cruz', 'Juchitán de Zaragoza', 'Santa Cruz Xoxocotlán', 'Huajuapan de León', 'Puerto Escondido', 'Santa María Huatulco', 'San Pedro Pochutla', 'Santo Domingo Tehuantepec', 'Matías Romero', 'Tlaxiaco', 'Asunción Nochixtlán', 'Miahuatlán de Porfirio Díaz', 'Pinotepa Nacional', 'Santa Lucía del Camino', 'Santa Cruz Amilpas', 'San Antonio de la Cal', 'Zimatlán de Álvarez', 'Ejutla de Crespo', 'Ocotlán de Morelos', 'Tlacolula de Matamoros', 'San Pablo Villa de Mitla', 'Villa de Zaachila', 'Cuicatlán', 'Teotitlán del Valle', 'Ciudad Ixtepec', 'Chahuites', 'San Blas Atempa', 'Santo Domingo Zanatepec', 'Villa de Etla'],
    },
    {
        estado: 'Puebla',
        ciudades: ['Puebla de Zaragoza', 'Tehuacán', 'San Martín Texmelucan de Labastida', 'Atlixco', 'San Pedro Cholula', 'San Andrés Cholula', 'Cuautlancingo', 'Teziutlán', 'Zacatlán', 'Huauchinango', 'Tepeaca', 'Amozoc de Mota', 'Coronango', 'Huejotzingo', 'Chignahuapan', 'Tecamachalco', 'Acatlán de Osorio', 'Izúcar de Matamoros', 'Xicotepec de Juárez', 'Ciudad Serdán', 'Tlatlauquitepec', 'Zacapoaxtla', 'Cuetzalan del Progreso', 'Tetela de Ocampo', 'Pahuatlán de Valle', 'Ajalpan', 'Quecholac', 'Palmar de Bravo', 'Chalchicomula de Sesma', 'Esperanza', 'Libres', 'Oriental', 'San Salvador El Seco', 'Xochiltepec', 'San Juan de los Llanos'],
    },
    {
        estado: 'Querétaro',
        ciudades: ['Santiago de Querétaro', 'San Juan del Río', 'Corregidora', 'El Marqués', 'Tequisquiapan', 'Pedro Escobedo', 'Cadereyta de Montes', 'Colón', 'Amealco de Bonfil', 'Jalpan de Serra', 'Ezequiel Montes', 'Huimilpan', 'Pinal de Amoles', 'Landa de Matamoros', 'San Joaquín', 'Arroyo Seco', 'Peñamiller', 'Tolimán'],
    },
    {
        estado: 'Quintana Roo',
        ciudades: ['Chetumal', 'Cancún', 'Playa del Carmen', 'Tulum', 'Cozumel', 'Felipe Carrillo Puerto', 'José María Morelos', 'Bacalar', 'Puerto Morelos', 'Isla Mujeres', 'Leona Vicario', 'Kantunilkín'],
    },
    {
        estado: 'San Luis Potosí',
        ciudades: ['San Luis Potosí', 'Soledad de Graciano Sánchez', 'Ciudad Valles', 'Matehuala', 'Rioverde', 'Tamazunchale', 'Ébano', 'Cárdenas', 'Cerritos', 'Ciudad Fernández', 'Santa María del Río', 'Villa de Reyes', 'Tamuín', 'Xilitla', 'Aquismón', 'Tancanhuitz de Santos', 'Axtla de Terrazas', 'Salinas de Hidalgo', 'Charcas', 'Venado', 'Moctezuma', 'Villa de Ramos', 'Ciudad del Maíz'],
    },
    {
        estado: 'Sinaloa',
        ciudades: ['Culiacán Rosales', 'Mazatlán', 'Los Mochis', 'Guasave', 'Guamúchil', 'Escuinapa de Hidalgo', 'El Fuerte', 'Navolato', 'Angostura', 'Mocorito', 'Sinaloa de Leyva', 'Choix', 'San Ignacio', 'Concordia', 'Rosario', 'La Cruz de Elota', 'Cosalá', 'Badiraguato'],
    },
    {
        estado: 'Sonora',
        ciudades: ['Hermosillo', 'Ciudad Obregón', 'Nogales', 'San Luis Río Colorado', 'Navojoa', 'Guaymas', 'Agua Prieta', 'Heroica Caborca', 'Cananea', 'Puerto Peñasco', 'Magdalena de Kino', 'Empalme', 'Huatabampo', 'Etchojoa', 'Villa Juárez', 'Sonoyta', 'Moctezuma', 'Ures', 'Álamos', 'Santa Ana', 'Naco', 'Ímuris', 'Altar', 'Pitiquito', 'Fronteras', 'Carbó', 'San Ignacio Río Muerto'],
    },
    {
        estado: 'Tabasco',
        ciudades: ['Villahermosa', 'Cárdenas', 'Comalcalco', 'Cunduacán', 'Paraíso', 'Teapa', 'Huimanguillo', 'Macuspana', 'Jalpa de Méndez', 'Nacajuca', 'Frontera', 'Jalapa', 'Tacotalpa', 'Tenosique de Pino Suárez', 'Balancán', 'Emiliano Zapata', 'Jonuta'],
    },
    {
        estado: 'Tamaulipas',
        ciudades: ['Reynosa', 'Heroica Matamoros', 'Nuevo Laredo', 'Tampico', 'Ciudad Victoria', 'Ciudad Madero', 'Altamira', 'Río Bravo', 'Ciudad Mante', 'San Fernando', 'Miguel Alemán', 'Camargo', 'Díaz Ordaz', 'Valle Hermoso', 'Xicoténcatl', 'González', 'Aldama', 'Soto la Marina', 'Jiménez', 'Tula', 'Ocampo', 'Antiguo Morelos', 'Nuevo Morelos', 'El Mante', 'Jaumave', 'Gómez Farías', 'Padilla', 'Llera', 'Casas', 'Güémez'],
    },
    {
        estado: 'Tlaxcala',
        ciudades: ['Tlaxcala de Xicohténcatl', 'Apizaco', 'Huamantla', 'Chiautempan', 'Zacatelco', 'Calpulalpan', 'Tlaxco', 'San Pablo del Monte', 'Contla de Juan Cuamatzi', 'Panotla', 'Totolac', 'Apetatitlán de Antonio Carvajal', 'Amaxac de Guerrero', 'Yauhquemehcan', 'Xaloztoc', 'Teolocholco', 'Acuamanala de Miguel Hidalgo', 'Tepeyanco', 'Nativitas', 'Ixtacuixtla de Mariano Matamoros', 'Hueyotlipan', 'Nanacamilpa de Mariano Arista', 'El Carmen Tequexquitla', 'Cuapiaxtla', 'Ixtenco', 'Zitlaltepec de Trinidad Sánchez Santos', 'Tetla de la Solidaridad', 'Terrenate', 'Altzayanca', 'Emiliano Zapata', 'Lázaro Cárdenas'],
    },
    {
        estado: 'Veracruz',
        ciudades: ['Heroica Veracruz', 'Xalapa-Enríquez', 'Coatzacoalcos', 'Córdoba', 'Orizaba', 'Poza Rica de Hidalgo', 'Boca del Río', 'Minatitlán', 'Papantla de Olarte', 'Tuxpan de Rodríguez Cano', 'Martínez de la Torre', 'San Andrés Tuxtla', 'Coatepec', 'Cosamaloapan de Carpio', 'Alvarado', 'Catemaco', 'Tlacotalpan', 'Agua Dulce', 'Las Choapas', 'Acayucan', 'San Juan Evangelista', 'Santiago Tuxtla', 'Tierra Blanca', 'Fortín de las Flores', 'Huatusco', 'Perote', 'Xico', 'Naolinco de Victoria', 'Misantla', 'Altotonga', 'Jalacingo', 'Tantoyuca', 'Pánuco', 'Cerro Azul', 'Álamo', 'Castillo de Teayo', 'Nanchital de Lázaro Cárdenas del Río', 'Tihuatlán', 'Gutiérrez Zamora', 'Tecolutla', 'Nautla', 'Vega de Alatorre', 'Emiliano Zapata', 'Úrsulo Galván', 'La Antigua', 'Paso de Ovejas', 'Soledad de Doblado', 'Cuitláhuac', 'Zongolica', 'Nogales', 'Río Blanco', 'Ixtaczoquitlán', 'Huiloapan de Cuauhtémoc'],
    },
    {
        estado: 'Yucatán',
        ciudades: ['Mérida', 'Valladolid', 'Tizimín', 'Progreso', 'Umán', 'Kanasín', 'Ticul', 'Tekax de Álvaro Obregón', 'Motul de Carrillo Puerto', 'Oxkutzcab', 'Izamal', 'Hunucmá', 'Conkal', 'Tixkokob', 'Temax', 'Espita', 'Sotuta', 'Peto', 'Tzucacab', 'Maxcanú', 'Halachó', 'Celestún', 'Chemax', 'Tinum', 'Kantunil', 'Muna', 'Santa Elena', 'Akil', 'Maní', 'Yaxcabá', 'Río Lagartos', 'San Felipe', 'Panabá', 'Dzemul', 'Telchac Puerto', 'Sinanché', 'Cansahcab', 'Tahdziú'],
    },
    {
        estado: 'Zacatecas',
        ciudades: ['Zacatecas', 'Guadalupe', 'Fresnillo', 'Jerez de García Salinas', 'Río Grande', 'Sombrerete', 'Loreto', 'Calera de Víctor Rosales', 'Jalpa', 'Nochistlán de Mejía', 'Villanueva', 'Teúl de González Ortega', 'Tabasco', 'Valparaíso', 'Monte Escobedo', 'Concepción del Oro', 'Mazapil', 'Pinos', 'Ojocaliente', 'Tlaltenango de Sánchez Román', 'Juchipila', 'Moyahua de Estrada', 'Apozol', 'Huanusco', 'Tepetongo', 'Cuauhtémoc', 'Genaro Codina', 'General Enrique Estrada', 'Morelos', 'Vetagrande', 'Trancoso', 'Villa García', 'Villa Hidalgo'],
    },
];
