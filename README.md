# AutoReset-amxx

Automatic stats reset. Mostly useful for communities who use more than one server.

Plugin saves last reset and shows when the next one will be available.

Cvar Explanation:            
  - stats_reset_type = 0, stats will reset every stats_reset_time days.
    EG: stats_reset_time = 3. stats will reset every 3 days.

  - stats_reset_type = 1, stats will reset every stats_reset_time weeks.
    EG: stats_reset_time = 3. stats will reset every 3 weeks.

  - stats_reset_type = 2, stats will reset every stats_reset_time months.
    EG: stats_reset_time = 3. stats will reset every 3 months.
    
Having next_reset in the database set to 0 ( 1st Jan 1970 ) won't automatically reset the server, neither with amx_stats_reset or amx_stats_reset_all ( setting csstats_reset 1, will tho ). 

It shouldn't support hlstats or gameme. Might check on that if requested.
