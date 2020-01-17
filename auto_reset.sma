#include < amxmodx >
#include < amxmisc >
#include < sqlx >

#if AMXX_VERSION_NUM < 183
    set_fail_state( "Plugin requires AMXX 1.8.3 or higher." );
#endif

#define ADMIN_SIMPLE        ADMIN_BAN               // reset one server
#define ADMIN_SUPER         ADMIN_LEVEL_A           // reset all servers in the db

new const host[] = "127.0.0.1";
new const user[] = "root";
new const pass[] = "";
new const db[]   = "mysql_dust";

new Handle:tuple;

new const table[] = "db_autoreset";

new const VERSION[] = "1.0";

new const Time[] = 
{
    86400,
    604800,
    2592000
}

new hostname[ 64 ];

new pResetType;
new pResetTime;

public plugin_init()
{
    register_plugin( "DB Reset Top15", VERSION, "DusT" );

    create_cvar( "AmX_DusT", "Auto_Reset", FCVAR_SPONLY | FCVAR_SERVER );

    register_concmd( "amx_stats_reset", "CmdReset", ADMIN_SIMPLE );
    register_concmd( "amx_stats_reset_all", "CmdResetAll", ADMIN_SUPER );

    /*
        Explanation:             
            - stats_reset_type = 0, stats will reset every stats_reset_time days.
              EG: stats_reset_time = 3. stats will reset every 3 days.
            
            - stats_reset_type = 1, stats will reset every stats_reset_time weeks.
              EG: stats_reset_time = 3. stats will reset every 3 weeks.

            - stats_reset_type = 2, stats will reset every stats_reset_time months.
              EG: stats_reset_time = 3. stats will reset every 3 months.
    */

    bind_pcvar_num( create_cvar( "stats_reset_type", "0", .description = "More info at github.com/amxDusT/AutoReset-amxx" ), pResetType );
    bind_pcvar_num( create_cvar( "stats_reset_time", "1", .description = "More info at github.com/amxDusT/AutoReset-amxx" ), pResetTime );

    tuple = SQL_MakeDbTuple( host, user, pass, db );
}

public plugin_cfg()
{
    set_task( 1.0, "SQL_Init" );
}

public SQL_Init()
{
    get_cvar_string( "hostname", hostname, charsmax( hostname ) );

    if( pResetType > 2 )
    {
        pResetType = 2;
        set_cvar_num( "stats_reset_type", 2 );
    }
    
    new query[ 256 ];
    
    formatex( query, charsmax( query ), "CREATE TABLE IF NOT EXISTS `%s`(\
                                            `id` INT NOT NULL AUTO_INCREMENT,\
                                            `hostname` VARCHAR(63) NOT NULL UNIQUE,\
                                            `last_reset` INT,\
                                            `next_reset` INT NOT NULL,\
                                            PRIMARY KEY(`id`)\
                                        );", table );
    
    SQL_ThreadQuery( tuple, "IgnoreHandle", query );

    formatex( query, charsmax( query ), "INSERT IGNORE INTO `%s` VALUES( NULL, '%s', NULL, %d );", table, hostname, get_systime() + ( Time[ pResetType ] * pResetTime ) );
    
    SQL_ThreadQuery( tuple, "IgnoreHandle", query );
}

public IgnoreHandle( failState, Handle:query, error[], errNum )
{
    if( errNum )
        set_fail_state( error );

    SQL_FreeHandle( query );    // pretty much useless but nothing to do
}

public CmdReset( id, level, cid )
{
    if( !cmd_access( id, level, cid, 0 ) )
        return PLUGIN_HANDLED;
    
    client_print( id, print_console, "** Stats will reset next map" );

    SQL_ThreadQuery( tuple, "IgnoreHandle", fmt( "UPDATE `%s` SET `next_reset`=UNIX_TIMESTAMP() WHERE `hostname`='%s';", table, hostname ) );
    
    return PLUGIN_HANDLED;
}

public CmdResetAll( id, level, cid )
{
    if( !cmd_access( id, level, cid, 0 ) )
        return PLUGIN_HANDLED;
    
    client_print( id, print_console, "** All stats will reset next map" );

    SQL_ThreadQuery( tuple, "IgnoreHandle", fmt( "UPDATE `%s` SET `next_reset`=UNIX_TIMESTAMP() WHERE `next_reset` > 86400;", table ) );

    return PLUGIN_HANDLED
}

public plugin_end()
{
    new Handle:connect;
    new errNum, error[ 256 ];
    connect = SQL_Connect( tuple, errNum, error, charsmax( error ) );
    if( errNum )
        set_fail_state( error );
    
    if( get_cvar_num( "csstats_reset" ) )
    {
        SQL_QueryAndIgnore( connect, "UPDATE `%s` SET `last_reset`=UNIX_TIMESTAMP(), `next_reset`=%d WHERE `hostname`='%s'", table, get_systime() + ( Time[ pResetType ] * pResetTime ), hostname );
        log_amx( "UPDATED LAST RESET AND NEXT RESET 1" );
        return;
    }
    new Handle:query = SQL_PrepareQuery( connect, "SELECT * FROM `%s` WHERE `hostname`='%s' AND `next_reset`<UNIX_TIMESTAMP() AND `next_reset`>86400;", table, hostname );
    
    if( !SQL_Execute( query ) )
        return;

    if( !SQL_NumResults( query ) )
        return;

    SQL_FreeHandle( query );
    server_cmd( "csstats_reset 1" );
    server_exec();

    SQL_QueryAndIgnore( connect, "UPDATE `%s` SET `next_reset`=UNIX_TIMESTAMP()+%d, `last_reset`=UNIX_TIMESTAMP() WHERE `hostname`='%s'", table, ( Time[ pResetType ] * pResetTime ), hostname );
    SQL_FreeHandle( connect );
    SQL_FreeHandle( tuple );
}
