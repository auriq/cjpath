######################################################
#  Create DB for CVpath analysis
######################################################
#
#  clear out the old .conf
ess server reset
#
# CJ table and profile vector
ess create database udb_cj --port 10020
ess create table cj s,pkey:uid i,tkey:t s:src s:domain s:page s:pname s:ref s:skey s:ip s:bwos is:fg s:ent i:visit i:pnum f:score s:gender s:age
ess create vector profile s,pkey:uid s:aux i:pv i:ss i:first_cv i:cv_cnt i:depth i:days s:skey s:bwos s:rgn i:imp i:click i:cst1 i:cst2 f:sum
ess create vector member s,pkey:uid s:memberid s:gender s:dob s:age
ess create variable i:vI1 i:vI2 i:vI3 i:vI4 i:vI5 s:vS1 i:cnt1 i:cnt2 i:cnt3 i:cnt4 i:cnt5 i:cnt6 i:cnt7 i:cnt8 i:cnt9 i:cnt10 i:cnt11 i:cnt12
ess create vector path s,pkey:Uid s,+nozero:p1 s,+nozero:p2 s,+nozero:p3 s,+nozero:p4 s,+nozero:p5 s,+nozero:p6 s,+nozero:p7 s,+nozero:p8 s,+nozero:p9 s,+nozero:p10 s,+nozero:p11 s,+nozero:p12 s,+nozero:p13 s,+nozero:p14 s,+nozero:p15 s,+nozero:p16 s,+nozero:p17 s,+nozero:p18 s,+nozero:p19 s,+nozero:p20

# For reports
ess create database udb_rslt --port 10021
ess create vector rslt s,pkey:key i,+add:tcnt i,+add:rcnt i,+max:tmax i,+min,+nozero:tmin i,+add:tave
#
# Count event 
ess create database udb_cnt --port 10022
ess create vector cnt s,pkey:key i,+add:pv i,+add:ss
#
# Count event and uu
ess create database udb_cntuu --port 10023
ess create vector cntuu s,pkey:key i,+add:pv i,+add:ss i,+add:uu

#
ess server commit
ess udbd stop
ess udbd start 10020
ess udbd start


