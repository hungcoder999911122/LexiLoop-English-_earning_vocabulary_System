package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

import java.time.LocalDateTime;

@Getter
@Setter
@Entity
@Table(name = "Quiz_Results")
public class QuizResult {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer resultID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "quizID")
    private Quiz quiz;

    @ManyToOne(optional = false)
    @JoinColumn(name = "userID")
    private User user;

    private Integer score;
    private Integer total;

    @Column(name = "duration_sec")
    private Integer durationSec;

    @Column(name = "taken_at", insertable = false, updatable = false)
    private LocalDateTime takenAt;
}
